<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateGroup;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * Estimate built FROM work packages with source evidence + badges.
 */
class EstimateFromWorkPackageTest extends WorkshopFlowTestCase
{
    protected function makeCriticalPadFindingWithPackage($service): array
    {
        [, , $pad] = $this->makeChecklistData();
        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas hampir habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN',
            'service_finding_id' => $finding->id,
            'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Ganti Kampas Rem', 'quantity' => 1, 'unit_price' => 75000, 'standard_minutes' => 30],
            ['item_type' => 'part', 'description' => 'Kampas Rem Depan', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        return [$finding, $package];
    }

    public function test_estimate_created_from_work_package_has_group(): void
    {
        $service = $this->makeService();
        [, $package] = $this->makeCriticalPadFindingWithPackage($service);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);

        $this->assertSame(ServiceEstimate::STATUS_DRAFT, $estimate->status);
        $this->assertSame(1, $estimate->groups()->count());

        $group = $estimate->groups()->firstOrFail();
        $this->assertSame($package->id, $group->service_work_package_id);
        $this->assertSame($package->title, $group->title);
        $this->assertSame(30, $group->standard_minutes);
        $this->assertEqualsWithDelta(255000.0, (float) $group->grand_total, 0.01);
        $this->assertSame(ServiceEstimateGroup::DECISION_PENDING, $group->customer_decision);

        // Header money recalculated from grouped items.
        $this->assertEqualsWithDelta(255000.0, (float) $estimate->grand_total, 0.01);
    }

    public function test_estimate_snapshot_carries_finding_evidence(): void
    {
        $service = $this->makeService();
        [$finding, $package] = $this->makeCriticalPadFindingWithPackage($service);
        $estimateService = app(EstimateService::class);

        $estimate = $estimateService->createDraft($service, [], [], [$package->id]);
        $estimateService->markSent($estimate, 'test');

        $snapshot = $estimate->fresh()->snapshot;
        $this->assertNotNull($snapshot['groups']);
        $this->assertSame($finding->finding_number, $snapshot['groups'][0]['finding_number']);
        $this->assertSame('Kampas Rem', $snapshot['groups'][0]['finding_title']);
        $this->assertSame('critical', $snapshot['groups'][0]['finding_severity']);
        $this->assertSame('1.2 mm', $snapshot['groups'][0]['finding_measurement']);
        $this->assertSame('GANTI KAMPAS REM DEPAN', $snapshot['groups'][0]['work_package_title']);
    }

    public function test_adding_same_package_twice_is_idempotent(): void
    {
        $service = $this->makeService();
        [, $package] = $this->makeCriticalPadFindingWithPackage($service);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);

        $flow = app(WorkshopFlowService::class);
        $flow->addWorkPackageToEstimate($estimate, $package);
        $flow->addWorkPackageToEstimate($estimate, $package);

        $this->assertSame(1, $estimate->groups()->count());
        $this->assertEqualsWithDelta(255000.0, (float) $estimate->fresh()->grand_total, 0.01);
    }

    public function test_manual_and_grouped_items_mix_in_totals(): void
    {
        $service = $this->makeService();
        [, $package] = $this->makeCriticalPadFindingWithPackage($service);

        $estimate = app(EstimateService::class)->createDraft($service, [], [
            ['item_type' => 'labor', 'description' => 'Manual Tune Up', 'quantity' => 1, 'unit_price' => 150000],
        ], [$package->id]);

        $this->assertEqualsWithDelta(405000.0, (float) $estimate->fresh()->grand_total, 0.01);
        $this->assertSame(1, $estimate->groups()->count());
    }

    public function test_severity_snapshot_preserved_on_group(): void
    {
        $service = $this->makeService();
        [$finding, $package] = $this->makeCriticalPadFindingWithPackage($service);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        $group = $estimate->groups()->firstOrFail();

        $this->assertSame($finding->severity, $group->severity_snapshot);
        $this->assertSame(ServiceFinding::SEVERITY_CRITICAL, $group->severity_snapshot);
    }

    public function test_package_status_proposes_finding(): void
    {
        $service = $this->makeService();
        [, $package] = $this->makeCriticalPadFindingWithPackage($service);

        $package->forceFill(['status' => ServiceWorkPackage::STATUS_PROPOSED])->save();
        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);

        $this->assertSame(ServiceEstimate::STATUS_DRAFT, $estimate->status);
        $this->assertSame(1, $estimate->groups()->count());
    }
}
