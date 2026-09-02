<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
use App\Services\EstimateService;
use App\Services\WorkshopFlowService;

/**
 * I. Approved work package → one task only, even if approval retries.
 * G. Rejected work package → no technician task.
 * Pending work → no task.
 */
class ApprovedWorkTaskCreationTest extends WorkshopFlowTestCase
{
    protected function makeApprovedEstimateWithPackage(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN', 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');

        $group = $estimate->groups()->firstOrFail();

        return [$estimate->fresh(), $group, $package];
    }

    public function test_approval_creates_exactly_one_task(): void
    {
        [$estimate, $group] = $this->makeApprovedEstimateWithPackage();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');

        $estimate = $estimate->fresh();
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimate->status);
        $this->assertSame(1, ServiceWorkTask::count());
    }

    public function test_repeated_approval_callbacks_do_not_duplicate_tasks(): void
    {
        [$estimate, $group] = $this->makeApprovedEstimateWithPackage();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');

        // Critical assertion I: retry paths never duplicate.
        $estimate = $estimate->fresh();
        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');
        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');
        $flow->createTasksForApprovedGroups($estimate);
        $flow->createTasksForApprovedGroups($estimate);

        $this->assertSame(1, ServiceWorkTask::count(), 'One task per approved work package — always.');
    }

    public function test_rejected_group_creates_no_task(): void
    {
        [$estimate, $group] = $this->makeApprovedEstimateWithPackage();
        $flow = app(WorkshopFlowService::class);

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'rejected'],
        ], 'public_link');

        // Critical assertion G: rejected work → no technician task.
        $this->assertSame(0, ServiceWorkTask::count());
    }

    public function test_pending_group_creates_no_task(): void
    {
        [$estimate] = $this->makeApprovedEstimateWithPackage();

        // Group stays pending (never decided) — no task may exist.
        $this->assertSame(0, ServiceWorkTask::count());
    }

    public function test_partial_approval_creates_task_for_approved_only(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $packageA = $flow->saveWorkPackage($service, [
            'title' => 'APPROVED WORK',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa A', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        $packageB = $flow->saveWorkPackage($service, [
            'title' => 'REJECTED WORK',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa B', 'quantity' => 1, 'unit_price' => 50000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$packageA->id, $packageB->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $estimate = $estimate->fresh();

        $groupA = $estimate->groups()->where('service_work_package_id', $packageA->id)->firstOrFail();
        $groupB = $estimate->groups()->where('service_work_package_id', $packageB->id)->firstOrFail();

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $groupA->id, 'decision' => 'approved'],
            ['group_id' => $groupB->id, 'decision' => 'rejected'],
        ], 'public_link');

        $tasks = ServiceWorkTask::all();
        $this->assertSame(1, $tasks->count());
        $this->assertSame($packageA->id, $tasks->first()->service_work_package_id);
    }

    public function test_task_ready_status_and_standard_minutes(): void
    {
        [$estimate, $group] = $this->makeApprovedEstimateWithPackage();
        app(WorkshopFlowService::class)->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::firstOrFail();
        $this->assertSame(ServiceWorkTask::STATUS_READY, $task->status);
        $this->assertSame(30, $task->standard_minutes);
        $this->assertNull($task->started_at);
    }

    public function test_package_flips_to_approved(): void
    {
        [$estimate, $group, $package] = $this->makeApprovedEstimateWithPackage();
        app(WorkshopFlowService::class)->submitGroupDecisions($estimate, [
            ['group_id' => $group->id, 'decision' => 'approved'],
        ], 'public_link');

        $this->assertSame(ServiceWorkPackage::STATUS_APPROVED, $package->fresh()->status);
    }
}
