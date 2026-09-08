<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\ServicePackage;
use App\Services\EstimateService;

class EstimateInvoiceLikeUxTest extends EstimateTestCase
{
    public function test_service_catalog_price_is_authoritative_for_normal_estimate_users(): void
    {
        $advisor = $this->makeUser('estimate_advisor');
        $this->grantPermission($advisor, 'estimates.create');
        $this->actingAs($advisor);

        $service = $this->makeService();
        $package = ServicePackage::create([
            'name' => 'JASA DIAGNOSA',
            'price' => 150000,
            'estimated_hours' => 1,
            'is_active' => true,
        ]);

        $this->post(route('services.estimates.store', $service), $this->storePayload([
            [
                'item_type' => 'labor',
                'service_catalog_id' => $package->id,
                'description' => 'Jasa Diagnosa',
                'quantity' => 1,
                'unit_price' => 1,
            ],
        ]))->assertRedirect();

        $estimate = ServiceEstimate::query()->where('service_id', $service->id)->firstOrFail();
        $item = $estimate->items->firstOrFail();
        $this->assertSame(150000.0, (float) $item->unit_price);
        $this->assertSame($package->id, $item->service_catalog_id);

        $draft = ServiceEstimate::query()
            ->with('items.serviceCatalog')
            ->findOrFail($estimate->id);
        $this->assertSame($package->id, $draft->items->firstOrFail()->serviceCatalog?->id);
    }

    public function test_estimate_print_has_invoice_like_money_columns_without_payment_semantics(): void
    {
        $service = $this->makeService();
        $estimate = app(EstimateService::class)->createDraft($service, [], [
            $this->itemPayload([
                'description' => 'JASA DIAGNOSA',
                'unit_price' => 150000,
                'discount' => 10000,
                'tax_rate' => 11,
            ]),
        ]);

        $this->get(route('estimates.print', $estimate))
            ->assertOk()
            ->assertSee('Diskon')
            ->assertSee('Pajak')
            ->assertSee('TOTAL ESTIMASI')
            ->assertDontSee('Bayar')
            ->assertDontSee('Pembayaran')
            ->assertDontSee('DP')
            ->assertDontSee('Sisa Tagihan')
            ->assertDontSee('Payment Status')
            ->assertDontSee('Jurnal')
            ->assertDontSee('Stok tersedia');
    }
}
