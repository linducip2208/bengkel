<?php

namespace Tests\Feature;

use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateGroup;
use App\Models\ServiceFinding;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Estimate revision must PRESERVE decision history: the superseded version
 * keeps its immutable evidence, the new version starts with pending decisions.
 */
class EstimateRevisionPreservesDecisionHistoryTest extends WorkshopFlowTestCase
{
    protected function makePartiallyApprovedEstimate(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $brakeType = ObservationType::create(['observation_type' => 'REM']);
        $pad = ObservationPoint::create(['observation_type_id' => $brakeType->id, 'observation_point' => 'Kampas Rem']);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Kampas', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas Rem', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $estimate = $estimate->fresh();
        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        return [$estimate->fresh(), $package];
    }

    public function test_revision_resets_decisions_but_keeps_history(): void
    {
        [$estimate, $package] = $this->makePartiallyApprovedEstimate();
        $estimateService = app(EstimateService::class);

        $revision = $estimateService->revise($estimate, [], [], 'Harga kampas naik');

        // Old version: superseded but evidence intact + immutable.
        $estimate = $estimate->fresh();
        $this->assertSame(ServiceEstimate::STATUS_SUPERSEDED, $estimate->status);
        $this->assertNotNull($estimate->approved_hash);
        $this->assertNotNull($estimate->decision_evidence);
        $this->assertEqualsWithDelta(255000.0, (float) $estimate->approved_total, 0.01);

        // New version: fresh decisions.
        $revision = $revision->fresh();
        $this->assertSame(2, $revision->version);
        $this->assertSame($estimate->id, $revision->previous_estimate_id);
        $this->assertSame(ServiceEstimate::STATUS_DRAFT, $revision->status);
        $this->assertNull($revision->decision_evidence);
        $this->assertEqualsWithDelta(0.0, (float) $revision->approved_total, 0.01);

        // Groups carried over with reset decisions.
        $groups = $revision->groups()->get();
        $this->assertSame(1, $groups->count());
        $this->assertSame(ServiceEstimateGroup::DECISION_PENDING, $groups->first()->customer_decision);
        $this->assertNull($groups->first()->decided_at);
        $this->assertSame($package->id, $groups->first()->service_work_package_id);
    }

    public function test_material_change_requires_revision(): void
    {
        [$estimate] = $this->makePartiallyApprovedEstimate();

        // Direct draft edit of an approved estimate must be rejected.
        $this->expectException(HttpException::class);
        app(EstimateService::class)->updateDraft($estimate->fresh(), [], []);
    }

    public function test_superseded_version_keeps_group_decisions(): void
    {
        [$estimate] = $this->makePartiallyApprovedEstimate();
        $estimateService = app(EstimateService::class);

        $estimateService->revise($estimate, [], [], 'revisi');

        $oldGroup = $estimate->groups()->firstOrFail();
        $this->assertSame(ServiceEstimateGroup::DECISION_APPROVED, $oldGroup->customer_decision, 'Historical decision evidence is never rewritten.');
        $this->assertNotNull($oldGroup->decided_at);
    }

    public function test_revision_via_endpoint_keeps_flow(): void
    {
        [$estimate] = $this->makePartiallyApprovedEstimate();
        $countBefore = ServiceEstimate::count();

        $this->post(route('estimates.revise', $estimate), [
            'revision_reason' => 'Test revisi via HTTP',
            'use_current_items' => '1',
        ])->assertRedirect();

        $this->assertSame($countBefore + 1, ServiceEstimate::count());
        $this->assertSame(ServiceEstimate::STATUS_DRAFT, ServiceEstimate::where('previous_estimate_id', $estimate->id)->firstOrFail()->status);
    }
}
