<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Services\EstimateService;

/**
 * Mandatory integrity test: editing a DRAFT must UPDATE the same row.
 * Revision creation is the only operation allowed to add estimate rows.
 */
class EstimateUpdateDoesNotDuplicateTest extends EstimateTestCase
{
    public function test_updating_draft_keeps_primary_key_and_row_count(): void
    {
        $service = $this->makeService();
        $product = $this->makeProduct();

        $this->post("/services/{$service->id}/estimates", $this->storePayload([$this->partPayload($product)]));

        $draft = ServiceEstimate::where('service_id', $service->id)->firstOrFail();
        $id = $draft->id;
        $beforeCount = ServiceEstimate::count();

        // Edit the draft (changed qty + added a labor line).
        $response = $this->put("/estimates/{$id}", $this->storePayload([
            $this->partPayload($product, ['quantity' => 3]),
            $this->itemPayload(['description' => 'TUNE UP', 'unit_price' => 200000]),
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $afterCount = ServiceEstimate::count();

        $this->assertSame($beforeCount, $afterCount, 'Draft update must not create a new estimate row');
        $this->assertSame($id, ServiceEstimate::findOrFail($id)->id, 'Primary key must stay identical');
        $this->assertSame(3.0, (float) ServiceEstimate::find($id)->items()->where('product_id', $product->id)->sum('quantity'));
        $this->assertSame(1, ServiceEstimate::where('service_id', $service->id)->count());
    }

    public function test_duplicate_submit_does_not_create_duplicate_estimate(): void
    {
        $service = $this->makeService();

        $payload = $this->storePayload([$this->itemPayload()]);

        $this->post("/services/{$service->id}/estimates", $payload)->assertRedirect();
        $this->post("/services/{$service->id}/estimates", $payload)->assertRedirect();
        $this->post("/services/{$service->id}/estimates", $payload)->assertRedirect();

        $this->assertSame(1, ServiceEstimate::where('service_id', $service->id)->count(), 'Duplicate submits must reuse the same draft');
        $this->assertSame(1, ServiceEstimate::count());
    }

    public function test_updating_issued_estimate_is_rejected_without_creating_rows(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);

        $beforeCount = ServiceEstimate::count();
        $originalTotal = (string) $estimate->grand_total;

        $response = $this->put("/estimates/{$estimate->id}", $this->storePayload([
            $this->itemPayload(['unit_price' => 999999999]),
        ]));

        $response->assertSessionHas('error');
        $this->assertSame($beforeCount, ServiceEstimate::count(), 'Rejected update must not create rows');
        $this->assertSame($originalTotal, (string) $estimate->fresh()->grand_total, 'Issued estimate must remain unchanged');
    }

    public function test_approved_estimate_is_immutable(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $originalHash = $estimate->approved_hash ?? 'x';

        app(EstimateService::class)->approve($estimate, 'public_link');

        $approved = $estimate->fresh();
        $total = (string) $approved->grand_total;
        $items = $approved->items->pluck('description')->all();

        $response = $this->put("/estimates/{$approved->id}", $this->storePayload([
            $this->itemPayload(['unit_price' => 100]),
        ]));

        $response->assertSessionHas('error');
        $fresh = $approved->fresh();
        $this->assertSame($total, (string) $fresh->grand_total);
        $this->assertSame($items, $fresh->items->pluck('description')->all());
        $this->assertSame($approved->approved_hash ?? $originalHash, $fresh->approved_hash);
        $this->assertNotSame(ServiceEstimate::STATUS_DRAFT, $fresh->status);
    }
}
