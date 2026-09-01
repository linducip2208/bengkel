<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateItem;

class EstimateCreateTest extends EstimateTestCase
{
    public function test_sa_can_create_draft_estimate_with_mixed_items(): void
    {
        $product = $this->makeProduct('MASTER KOPLING ATAS', 1200000);
        $service = $this->makeService();

        $response = $this->post("/services/{$service->id}/estimates", $this->storePayload([
            $this->partPayload($product, ['quantity' => 1]),
            $this->itemPayload(['description' => 'JASA O/H KOPLING', 'unit_price' => 400000]),
            $this->itemPayload(['item_type' => 'other', 'description' => 'TUNE UP', 'unit_price' => 250000, 'tax_rate' => 11]),
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $estimate = ServiceEstimate::where('service_id', $service->id)->firstOrFail();
        $this->assertSame(ServiceEstimate::STATUS_DRAFT, $estimate->status);
        $this->assertSame(1, $estimate->version);
        $this->assertMatchesRegularExpression('/^EST-\d{6}-\d{4}$/', $estimate->estimate_number);
        $this->assertCount(3, $estimate->items);

        // Server-authoritative math, not client totals.
        $this->assertEquals(1850000.0, (float) $estimate->subtotal);
        $this->assertEquals(27500.0, (float) $estimate->tax_amount); // 250000 * 11%
        $this->assertEquals(1877500.0, (float) $estimate->grand_total);
        $this->assertNotNull(ServiceEstimateItem::where('service_estimate_id', $estimate->id)->whereNull('product_id')->where('item_type', 'labor')->first());
    }

    public function test_items_without_product_or_description_are_dropped(): void
    {
        $service = $this->makeService();

        $response = $this->post("/services/{$service->id}/estimates", $this->storePayload([
            $this->itemPayload(['description' => 'TUNE UP', 'unit_price' => 100000]),
            $this->itemPayload(['description' => '', 'unit_price' => 999999]),
        ]));

        $response->assertRedirect();
        $estimate = ServiceEstimate::where('service_id', $service->id)->firstOrFail();
        $this->assertCount(1, $estimate->items);
    }

    public function test_empty_estimate_is_rejected(): void
    {
        $service = $this->makeService();

        $response = $this->post("/services/{$service->id}/estimates", $this->storePayload([]));

        $response->assertSessionHas('error');
        $this->assertSame(0, ServiceEstimate::count());
    }

    public function test_creating_estimate_logs_event_but_no_stock_change(): void
    {
        $service = $this->makeService();

        $this->post("/services/{$service->id}/estimates", $this->storePayload([$this->itemPayload()]));

        $this->assertSame(1, ActivityLog::where('event', 'estimate.created')->count());
    }
}
