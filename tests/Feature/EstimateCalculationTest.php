<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Services\EstimateService;

class EstimateCalculationTest extends EstimateTestCase
{
    public function test_line_and_document_calculations_are_server_authoritative(): void
    {
        $service = $this->makeService();
        $product = $this->makeProduct('KOPLING SET', 1600000);

        // Client sends bogus totals — server must ignore them.
        $response = $this->post("/services/{$service->id}/estimates", $this->storePayload(
            [
                $this->partPayload($product, ['quantity' => 2]),
                $this->itemPayload(['description' => 'JASA GANTI SHOCK BREAKER', 'unit_price' => 250000, 'discount' => 50000]),
            ],
            ['items_totals_bogus' => '1'],
        ));

        $response->assertRedirect();
        $estimate = ServiceEstimate::where('service_id', $service->id)->firstOrFail();

        // subtotal = Σ (qty × price) = 3.200.000 + 250.000; grand = subtotal − 50.000
        $this->assertEquals(3450000.0, (float) $estimate->subtotal);
        $this->assertEquals(50000.0, (float) $estimate->discount);
        $this->assertEquals(3400000.0, (float) $estimate->grand_total);
        $this->assertEquals(3200000.0, (float) $estimate->items()->where('product_id', $product->id)->first()->line_total);
        $this->assertEquals(200000.0, $estimate->items()->where('item_type', 'labor')->first()->line_total);
    }

    public function test_percent_discount_on_line_and_document(): void
    {
        $service = $this->makeService();

        $response = $this->post("/services/{$service->id}/estimates", $this->storePayload(
            [$this->itemPayload(['unit_price' => 1000000, 'discount' => 10, 'discount_type' => 'percent'])],
            ['discount' => 5, 'discount_type' => 'percent'],
        ));

        $response->assertRedirect();
        $estimate = ServiceEstimate::where('service_id', $service->id)->firstOrFail();

        $this->assertEquals(1000000.0, (float) $estimate->subtotal);
        // line disc 100.000 + document 5% of subtotal (1.000.000) = 50.000
        $this->assertEquals(150000.0, (float) $estimate->discount);
        $this->assertEquals(850000.0, (float) $estimate->grand_total);
    }

    public function test_decimal_quantity_rounds_to_two_decimals(): void
    {
        $service = $this->makeService();

        $this->post("/services/{$service->id}/estimates", $this->storePayload([
            $this->itemPayload(['description' => ' Oli Bahan (liter)', 'quantity' => 3.755, 'unit_price' => 10000]),
        ]));

        $estimate = ServiceEstimate::where('service_id', $service->id)->firstOrFail();
        $item = $estimate->items->first();

        $this->assertEquals(3.755, (float) $item->quantity);
        $this->assertEquals(37550.0, (float) $item->line_total);
        $this->assertEquals(37550.0, (float) $estimate->grand_total);
    }

    public function test_without_tax_totals_match_line_sum(): void
    {
        $service = $this->makeService();

        $this->post("/services/{$service->id}/estimates", $this->storePayload([
            $this->itemPayload(['unit_price' => 100000, 'tax_rate' => null]),
            $this->itemPayload(['description' => 'TUNE UP', 'unit_price' => 150000, 'tax_rate' => 0]),
        ]));

        $estimate = ServiceEstimate::where('service_id', $service->id)->firstOrFail();
        $this->assertEquals(250000.0, (float) $estimate->grand_total);
        $this->assertEquals(0.0, (float) $estimate->tax_amount);
    }

    public function test_line_discount_never_exceeds_line_base(): void
    {
        $svc = app(EstimateService::class);

        $line = $svc->computeLine(['quantity' => 2, 'unit_price' => 10000, 'discount' => 999999, 'discount_type' => 'fixed']);

        $this->assertEquals(0.0, $line['line_total']);
        $this->assertEquals(20000.0, $line['discount']);
    }

    public function test_negative_or_malicious_input_is_clamped(): void
    {
        $service = $this->makeService();

        $response = $this->post("/services/{$service->id}/estimates", $this->storePayload(
            [$this->itemPayload(['quantity' => -5, 'unit_price' => -100])],
        ));

        $response->assertSessionHasErrors(); // negative numeric fails validation
        $this->assertSame(0, ServiceEstimate::count());
    }
}
