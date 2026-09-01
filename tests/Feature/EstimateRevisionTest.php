<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateItem;
use App\Services\EstimateService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EstimateRevisionTest extends EstimateTestCase
{
    public function test_revision_creates_new_version_and_supersedes_old(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $estimateService->approve($estimate, 'public_link');

        $beforeCount = ServiceEstimate::count();
        $oldTotal = (string) $estimate->grand_total;

        $revision = $estimateService->revise(
            $estimate,
            [],
            [$this->itemPayload(['unit_price' => 400000]), $this->itemPayload(['description' => 'RACK STEERING SEAL', 'unit_price' => 500000])],
            'Ditemukan kerusakan tambahan',
        );

        $this->assertSame($beforeCount + 1, ServiceEstimate::count(), 'Only revision may increase row count');
        $this->assertSame(2, $revision->version);
        $this->assertMatchesRegularExpression('/^EST-\d{6}-\d{4}$/', $revision->estimate_number, 'Revision gets a fresh unique document number');
        $this->assertNotSame($estimate->estimate_number, $revision->estimate_number);
        $this->assertSame($estimate->id, $revision->previous_estimate_id);
        $this->assertSame(ServiceEstimate::STATUS_SUPERSEDED, $estimate->fresh()->status);
        $this->assertSame($oldTotal, (string) $estimate->fresh()->grand_total, 'Old approved version unchanged');

        $this->assertSame(1, ActivityLog::where('event', 'estimate.revised')->count());
        $this->assertSame('Ditemukan kerusakan tambahan', ActivityLog::where('event', 'estimate.revised')->first()->changes['reason'] ?? null);
    }

    public function test_revision_of_superseded_estimate_is_blocked(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $v1 = $this->issueEstimate($service);
        $v2 = $estimateService->revise($v1, [], [$this->itemPayload()]);
        $estimateService->markSent($v2, 'test');

        $this->expectException(HttpException::class);

        $estimateService->revise($v1, [], [$this->itemPayload()]);
    }

    public function test_revision_items_copied_when_payload_empty(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $product = $this->makeProduct();
        $estimate = $this->issueEstimate($service, [$this->partPayload($product, ['quantity' => 4])]);

        $revision = $estimateService->revise($estimate, [], [], 'add work');

        $revision->refresh();
        $this->assertCount(1, $revision->items);
        $this->assertEquals(4.0, (float) $revision->items->first()->quantity);
        $this->assertSame($product->id, $revision->items->first()->product_id);
    }

    public function test_latest_approved_revision_wins_for_service_charge(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $v1 = $this->issueEstimate($service, [$this->itemPayload(['unit_price' => 100000])]);
        $estimateService->approve($v1, 'public_link');
        $this->assertEquals(100000.0, (float) $service->fresh()->charge);

        $v2 = $estimateService->revise($v1, [], [$this->itemPayload(['unit_price' => 100000]), $this->itemPayload(['description' => 'TAMBAHAN', 'unit_price' => 50000])]);
        $estimateService->markSent($v2, 'test');
        $estimateService->approve($v2, 'public_link');

        $this->assertEquals(150000.0, (float) $service->fresh()->charge);
        $this->assertSame(ServiceEstimate::STATUS_SUPERSEDED, $v1->fresh()->status);
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $v2->fresh()->status);
    }

    public function test_version_history_preserves_all_items_immutable(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $v1 = $this->issueEstimate($service, [$this->itemPayload(['description' => 'ITEM LAMA', 'unit_price' => 70000])]);

        $revision = $estimateService->revise($v1, [], [$this->itemPayload(['description' => 'ITEM BARU', 'unit_price' => 90000])]);

        $v1Descriptions = ServiceEstimateItem::where('service_estimate_id', $v1->id)->pluck('description')->all();
        $this->assertSame(['ITEM LAMA'], $v1Descriptions, 'V1 items must remain untouched');
        $this->assertSame(['ITEM BARU'], $revision->items->pluck('description')->all());
    }
}
