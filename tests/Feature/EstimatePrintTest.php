<?php

namespace Tests\Feature;

use App\Services\EstimateService;

class EstimatePrintTest extends EstimateTestCase
{
    public function test_print_contains_customer_facing_estimate_contents_without_payment_or_stock_semantics(): void
    {
        $service = $this->makeService();
        $product = $this->makeProduct('KAMPAS REM', 350000);
        $estimate = app(EstimateService::class)->createDraft($service, [], [
            $this->partPayload($product),
            $this->itemPayload(['description' => 'JASA GANTI REM', 'unit_price' => 75000]),
        ]);

        $this->get(route('estimates.print', $estimate))
            ->assertOk()
            ->assertSee($estimate->estimate_number)
            ->assertSee($service->customer->name)
            ->assertSee($product->name)
            ->assertSee('JASA GANTI REM')
            ->assertSee('TOTAL ESTIMASI')
            ->assertDontSee('Belum Dibayar')
            ->assertDontSee('Sisa Pembayaran')
            ->assertDontSee('Stok tersedia')
            ->assertDontSee('Harga Pokok')
            ->assertDontSee('Margin');
    }
}
