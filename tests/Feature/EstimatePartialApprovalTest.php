<?php

namespace Tests\Feature;

use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Per-work-package customer decisions + partial approval derivation.
 */
class EstimatePartialApprovalTest extends WorkshopFlowTestCase
{
    protected function makeTwoPackageEstimate(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        // Two distinct points: brake (critical) + oil (attention).
        $brakeType = ObservationType::create(['observation_type' => 'REM']);
        $engine = ObservationType::create(['observation_type' => 'MESIN']);
        $pad = ObservationPoint::create(['observation_type_id' => $brakeType->id, 'observation_point' => 'Kampas Rem']);
        $oil = ObservationPoint::create(['observation_type_id' => $engine->id, 'observation_point' => 'Oli Mesin']);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
            $oil->id => ['condition_status' => 'attention'],
        ]);

        $padFinding = ServiceFinding::where('service_id', $service->id)->where('severity', 'critical')->firstOrFail();
        $oilFinding = ServiceFinding::where('service_id', $service->id)->where('severity', 'attention')->firstOrFail();

        $padPackage = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM', 'service_finding_id' => $padFinding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Kampas', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas Rem', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        $oilPackage = $flow->saveWorkPackage($service, [
            'title' => 'GANTI OLI', 'service_finding_id' => $oilFinding->id, 'standard_minutes' => 15,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Oli', 'quantity' => 1, 'unit_price' => 30000],
            ['item_type' => 'part', 'description' => 'Oli', 'quantity' => 1, 'unit_price' => 120000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$padPackage->id, $oilPackage->id]);
        app(EstimateService::class)->markSent($estimate, 'test');

        $estimate = $estimate->fresh();

        return [$estimate, $estimate->groups()->where('title', 'GANTI KAMPAS REM')->firstOrFail(), $estimate->groups()->where('title', 'GANTI OLI')->firstOrFail()];
    }

    public function test_partial_approval_derives_status_and_totals(): void
    {
        [$estimate, $brakeGroup, $oilGroup] = $this->makeTwoPackageEstimate();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $brakeGroup->id, 'decision' => 'approved'],
            ['group_id' => $oilGroup->id, 'decision' => 'rejected', 'reason' => 'Belum perlu'],
        ], 'public_link');

        $estimate = $estimate->fresh();

        // Critical assertion F: approved_total includes only the approved one.
        $this->assertSame(ServiceEstimate::STATUS_PARTIALLY_APPROVED, $estimate->status);
        $this->assertSame(ServiceEstimate::DECISION_PARTIALLY_APPROVED, $estimate->decision_status);
        $this->assertEqualsWithDelta(255000.0, (float) $estimate->approved_total, 0.01);
        $this->assertEqualsWithDelta(150000.0, (float) $estimate->rejected_total, 0.01);
    }

    public function test_full_approval_and_full_rejection_derive_correctly(): void
    {
        [$estimateA, $brakeA, $oilA] = $this->makeTwoPackageEstimate();
        [$estimateR, $brakeR, $oilR] = $this->makeTwoPackageEstimate();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimateA, [
            ['group_id' => $brakeA->id, 'decision' => 'approved'],
            ['group_id' => $oilA->id, 'decision' => 'approved'],
        ], 'public_link');
        $estimateA = $estimateA->fresh();
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimateA->status);
        $this->assertEqualsWithDelta(405000.0, (float) $estimateA->approved_total, 0.01);

        $flow->submitGroupDecisions($estimateR, [
            ['group_id' => $brakeR->id, 'decision' => 'rejected'],
            ['group_id' => $oilR->id, 'decision' => 'rejected'],
        ], 'public_link');
        $estimateR = $estimateR->fresh();
        $this->assertSame(ServiceEstimate::STATUS_REJECTED, $estimateR->status);
        $this->assertEqualsWithDelta(0.0, (float) $estimateR->approved_total, 0.01);
        $this->assertEqualsWithDelta(405000.0, (float) $estimateR->rejected_total, 0.01);
    }

    public function test_decisions_persist_with_timestamps_and_evidence(): void
    {
        [$estimate, $brakeGroup, $oilGroup] = $this->makeTwoPackageEstimate();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $brakeGroup->id, 'decision' => 'approved'],
            ['group_id' => $oilGroup->id, 'decision' => 'rejected', 'reason' => 'Tahun depan saja'],
        ], 'public_link');

        $brakeGroup = $brakeGroup->fresh();
        $oilGroup = $oilGroup->fresh();

        $this->assertSame('approved', $brakeGroup->customer_decision);
        $this->assertNotNull($brakeGroup->decided_at);

        $this->assertSame('rejected', $oilGroup->customer_decision);
        $this->assertSame('Tahun depan saja', $oilGroup->decision_reason);

        $estimate = $estimate->fresh();
        $this->assertNotNull($estimate->decision_evidence);
        $this->assertSame('public_link', $estimate->decision_evidence['method']);
        $this->assertCount(2, $estimate->decision_evidence['decisions']);
        $this->assertNotNull($estimate->approved_hash);
    }

    public function test_service_charge_uses_approved_amount_only(): void
    {
        [$estimate, $brakeGroup, $oilGroup] = $this->makeTwoPackageEstimate();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $brakeGroup->id, 'decision' => 'approved'],
            ['group_id' => $oilGroup->id, 'decision' => 'rejected'],
        ], 'public_link');

        $service = $estimate->service()->withoutGlobalScopes()->first();
        // Critical assertion (19): service commercial value = approved only.
        $this->assertEqualsWithDelta(255000.0, (float) $service->charge, 0.01);
    }

    public function test_rejected_package_marked_and_not_pending(): void
    {
        [$estimate, $brakeGroup, $oilGroup] = $this->makeTwoPackageEstimate();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $brakeGroup->id, 'decision' => 'approved'],
            ['group_id' => $oilGroup->id, 'decision' => 'rejected'],
        ], 'public_link');

        $oilPackage = ServiceWorkPackage::whereKey($oilGroup->fresh()->service_work_package_id)->firstOrFail();
        $this->assertSame(ServiceWorkPackage::STATUS_REJECTED, $oilPackage->status);
    }

    public function test_customer_cannot_decide_before_estimate_is_sent(): void
    {
        $service = $this->makeService();
        [, , $pad] = $this->makeChecklistData();
        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();
        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM', 'service_finding_id' => $finding->id,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        $group = $estimate->groups()->firstOrFail();

        $this->expectException(HttpException::class);
        app(WorkshopFlowService::class)->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');
    }
}
