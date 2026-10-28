<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PartReservation;
use App\Models\ServiceWorkPackage;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Services\EstimateService;
use App\Services\WorkshopFlowService;

/**
 * D1: konversi estimasi tidak boleh decrement ulang part yang sudah
 * dikonsumsi servis tertaut (consume reservasi / input part servis).
 * Stok berkurang tepat sekali per pemakaian fisik.
 */
class EstimateConvertStockDedupTest extends EstimateTestCase
{
    private function approvedPackageEstimate(int $partQty = 2): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $product = $this->makeProduct('KAMPAS DEDUP');

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS',
        ], [
            ['item_type' => 'part', 'description' => 'Kampas', 'quantity' => $partQty, 'unit_price' => 180000, 'product_id' => $product->id],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $group = $estimate->groups()->firstOrFail();

        app(WorkshopFlowService::class)->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');

        // Alur modern: paket harus selesai + lulus QC sebelum bisa konversi.
        $package->update(['status' => ServiceWorkPackage::STATUS_QC_PASSED]);

        return [$estimate->fresh(), $service, $product];
    }

    private function stockOf(int $productId): float
    {
        return (float) StockRecord::where('product_id', $productId)->firstOrFail()->quantity;
    }

    private function outDecremented(int $invoiceId): float
    {
        return (float) StockHistory::where('reference_type', Invoice::class)
            ->where('reference_id', $invoiceId)
            ->where('type', 'out')
            ->sum('quantity_change');
    }

    public function test_convert_skips_stock_for_consumed_reservation(): void
    {
        [$estimate, $service, $product] = $this->approvedPackageEstimate(2);
        $this->grantPermission($this->user, 'service.edit');

        $reservation = PartReservation::where('service_id', $service->id)->firstOrFail();
        $this->post(route('services.reservations.consume', $reservation))->assertRedirect();
        $this->assertSame('consumed', $reservation->fresh()->status);
        $this->assertSame(98.0, $this->stockOf($product->id));

        $invoice = app(EstimateService::class)->convertToInvoice($estimate->fresh());

        // Tidak ada decrement kedua: stok tetap 98, baris invoice tetap 2.
        $this->assertSame(98.0, $this->stockOf($product->id));
        $this->assertSame(0.0, abs($this->outDecremented($invoice->id)));
        $this->assertEqualsWithDelta(2.0, (float) $invoice->items()->withoutGlobalScopes()->whereNotNull('product_id')->firstOrFail()->quantity, 0.001);
    }

    public function test_partial_consumption_decrements_only_remainder(): void
    {
        [$estimate, $service, $product] = $this->approvedPackageEstimate(4);
        $this->grantPermission($this->user, 'service.edit');

        // Konsumsi sebagian (2 dari 4) via reservasi terpisah yang lebih kecil.
        $reservation = PartReservation::where('service_id', $service->id)->firstOrFail();
        $reservation->update(['quantity' => 2]);
        $this->post(route('services.reservations.consume', $reservation))->assertRedirect();
        $this->assertSame(98.0, $this->stockOf($product->id));

        $invoice = app(EstimateService::class)->convertToInvoice($estimate->fresh());

        // Sisa 2 dari 4 didecrement saat konversi: total keluar 4, stok 96.
        $this->assertSame(96.0, $this->stockOf($product->id));
        $this->assertEqualsWithDelta(-2.0, $this->outDecremented($invoice->id), 0.001);
    }

    public function test_convert_without_consumption_decrements_fully(): void
    {
        $service = $this->makeService();
        $product = $this->makeProduct('KOPLING DEDUP');

        $estimate = app(EstimateService::class)->createDraft($service, [], [
            $this->partPayload($product, ['quantity' => 1]),
        ]);
        app(EstimateService::class)->markSent($estimate, 'test');
        app(EstimateService::class)->approve($estimate, 'public_link');

        $invoice = app(EstimateService::class)->convertToInvoice($estimate);

        // Perilaku lama dipertahankan bila tidak ada pemakaian tercatat.
        $this->assertSame(99.0, $this->stockOf($product->id));
        $this->assertEqualsWithDelta(-1.0, $this->outDecremented($invoice->id), 0.001);
    }
}
