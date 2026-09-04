<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;
use App\Services\WorkshopProgressService;

class SimplePreEstimateProgressTest extends WorkshopFlowTestCase
{
    public function test_projection_exposes_only_check_in_findings_and_estimate(): void
    {
        $progress = app(WorkshopProgressService::class)->simplePreEstimateProgress($this->makeService(['workflow_status' => 1]));

        $this->assertSame(['check_in', 'findings', 'estimate'], array_keys($progress['steps']));
        $this->assertSame('findings', $progress['current_step']);
        $this->assertSame('tab-findings', $progress['next_action']['target']);
    }

    public function test_work_plan_and_draft_are_reflected_without_mutating_records(): void
    {
        $service = $this->makeService();
        [$point] = $this->makeChecklistData();
        app(ObservationService::class)->saveCheckResults($service, [
            $point->id => ['condition_status' => 'critical', 'comment' => 'Perlu diganti'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();
        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'Rencana Perbaikan',
            'service_finding_id' => $finding->id,
        ], [[
            'item_type' => 'labor', 'description' => 'Pekerjaan teknisi', 'quantity' => 1, 'unit_price' => 100000,
        ]]);
        app(EstimateService::class)->createDraft($service, [], [], [$package->id]);

        $progress = app(WorkshopProgressService::class)->simplePreEstimateProgress($service->fresh());

        $this->assertSame('Temuan', $progress['steps']['findings']['label']);
        $this->assertSame(ServiceEstimate::STATUS_DRAFT, $progress['steps']['estimate']['data']['estimate']->status);
        $this->assertDatabaseCount('service_work_packages', 1);
        $this->assertDatabaseCount('service_estimates', 1);
    }
}
