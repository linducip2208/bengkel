<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\ServiceWorkPackage;
use App\Services\EstimateService;
use App\Services\WorkshopFlowService;

/**
 * Server-authoritative money + standard/actual time separation.
 */
class WorkPackageCalculationTest extends WorkshopFlowTestCase
{
    public function test_totals_are_server_computed_from_items(): void
    {
        $service = $this->makeService();

        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN',
            'standard_minutes' => 0,
        ], [
            ['item_type' => 'labor', 'description' => 'Ganti Kampas Rem', 'quantity' => 1, 'unit_price' => 75000, 'standard_minutes' => 30],
            ['item_type' => 'part', 'description' => 'Kampas Rem Depan', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        $totals = $package->computeTotals();
        $this->assertEqualsWithDelta(75000.0, $totals['labor_total'], 0.01);
        $this->assertEqualsWithDelta(180000.0, $totals['part_total'], 0.01);
        $this->assertEqualsWithDelta(0.0, $totals['other_total'], 0.01);
        $this->assertEqualsWithDelta(255000.0, $totals['grand_total'], 0.01);
        $this->assertSame(30, $totals['standard_minutes']);
    }

    public function test_decimal_quantity_multiplies_exactly(): void
    {
        $service = $this->makeService();

        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'ISI OLI 3.5 LITER',
        ], [
            ['item_type' => 'part', 'description' => 'Oli', 'quantity' => 3.5, 'unit_price' => 60000],
        ]);

        $totals = $package->computeTotals();
        $this->assertEqualsWithDelta(210000.0, $totals['grand_total'], 0.01);
    }

    public function test_standard_time_is_separate_from_actual_time(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI OLI',
            'standard_minutes' => 15,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Ganti Oli', 'quantity' => 1, 'unit_price' => 30000, 'standard_minutes' => 15],
        ]);

        // Approve + create task.
        $package->forceFill(['status' => ServiceWorkPackage::STATUS_APPROVED])->save();
        $flow->createTasksForApprovedGroups($this->makeGroupedEstimate($package));

        $task = $package->task()->firstOrFail();

        // Work 10 min, pause 20 min, resume and work 5 min.
        $flow->startTask($task);
        $this->travel(10)->minutes();
        $flow->pauseTask($task);
        $this->travel(20)->minutes();
        $flow->startTask($task);
        $this->travel(5)->minutes();
        $flow->finishTask($task);
        $this->travelBack();
        $task->refresh();

        // Actual = 15 min of work; the 20-minute pause is excluded.
        $this->assertSame(15, $task->actualMinutes());
        // Standard time was NEVER overwritten by actual work.
        $this->assertSame(15, $task->standard_minutes);
        $package->refresh();
        $this->assertSame(15, $package->standard_minutes);
    }

    /**
     * Helper: create a minimal approved estimate that links a package so
     * task creation can flow through the real service path.
     */
    protected function makeGroupedEstimate(ServiceWorkPackage $package): ServiceEstimate
    {
        $estimateService = app(EstimateService::class);
        $service = $package->service()->withoutGlobalScopes()->first() ?? $package->service;

        $estimate = $estimateService->createDraft($service, [], [], [$package->id]);
        $estimate->forceFill([
            'status' => ServiceEstimate::STATUS_APPROVED,
            'decision_status' => ServiceEstimate::DECISION_APPROVED,
        ])->save();
        $estimate->groups()->update(['customer_decision' => 'approved']);
        $estimateService->recalculateApprovedAmounts($estimate);

        return $estimate;
    }
}
