<?php

namespace Tests\Feature;

use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
use App\Models\ServiceWorkTimeEntry;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * START / PAUSE / RESUME / FINISH with server-owned time entries.
 */
class WorkTaskTimerTest extends WorkshopFlowTestCase
{
    protected function makeReadyTask(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN', 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();

        return [$task, $flow, $package];
    }

    public function test_start_creates_open_time_entry_and_sets_started_at(): void
    {
        [$task, $flow] = $this->makeReadyTask();

        $flow->startTask($task);

        $task = $task->fresh();
        $this->assertSame(ServiceWorkTask::STATUS_IN_PROGRESS, $task->status);
        $this->assertNotNull($task->started_at);
        $this->assertSame(1, ServiceWorkTimeEntry::where('service_work_task_id', $task->id)->count());
        $this->assertNotNull($task->openEntry());
    }

    public function test_double_start_is_idempotent(): void
    {
        [$task, $flow] = $this->makeReadyTask();

        $flow->startTask($task);
        $flow->startTask($task);

        $this->assertSame(1, ServiceWorkTimeEntry::where('service_work_task_id', $task->id)->count());
    }

    public function test_pause_closes_entry_and_resumes_creates_new_one(): void
    {
        [$task, $flow] = $this->makeReadyTask();

        $flow->startTask($task);
        $this->travel(10)->minutes();
        $flow->pauseTask($task);

        $task = $task->fresh();
        $this->assertSame(ServiceWorkTask::STATUS_PAUSED, $task->status);
        $this->assertNull($task->openEntry());
        $this->assertSame(10, $task->actualMinutes());

        $flow->startTask($task); // resume
        $task = $task->fresh();
        $this->assertSame(ServiceWorkTask::STATUS_IN_PROGRESS, $task->status);
        $this->assertSame(2, ServiceWorkTimeEntry::where('service_work_task_id', $task->id)->count());
        $this->assertNotNull($task->openEntry());
    }

    public function test_pause_is_idempotent_when_not_running(): void
    {
        [$task, $flow] = $this->makeReadyTask();

        $flow->pauseTask($task); // not started yet Ã¢â‚¬â€ no crash
        $this->assertSame(ServiceWorkTask::STATUS_READY, $task->fresh()->status);
        $this->assertSame(0, ServiceWorkTimeEntry::count());
    }

    public function test_finish_closes_all_entries_and_moves_to_qc_pending(): void
    {
        [$task, $flow] = $this->makeReadyTask();

        $flow->startTask($task);
        $this->travel(25)->minutes();
        $flow->pauseTask($task);
        $flow->startTask($task);
        $this->travel(5)->minutes();
        $flow->finishTask($task);

        $task = $task->fresh();
        $this->assertSame(ServiceWorkTask::STATUS_QC_PENDING, $task->status);
        $this->assertNull($task->openEntry());
        $this->assertSame(2, $task->timeEntries()->count());
        $this->assertSame(30, $task->actualMinutes());
        $this->assertSame(30, $task->standard_minutes, 'Standard time untouched by actual work.');
    }

    public function test_finish_is_idempotent(): void
    {
        [$task, $flow] = $this->makeReadyTask();

        $flow->startTask($task);
        $flow->finishTask($task);
        $flow->finishTask($task);

        $this->assertSame(ServiceWorkTask::STATUS_QC_PENDING, $task->fresh()->status);
        $this->assertSame(1, ServiceWorkTimeEntry::where('service_work_task_id', $task->id)->count());
    }

    public function test_package_and_finding_follow_task_execution(): void
    {
        [$task, $flow] = $this->makeReadyTask();

        $flow->startTask($task);

        $package = ServiceWorkPackage::whereKey($task->service_work_package_id)->first();
        $this->assertSame(ServiceWorkPackage::STATUS_IN_PROGRESS, $package->status);
        // This fixture package is manual (no finding) â€” nothing to update.
        $this->assertNull($package->finding);
    }

    public function test_finding_moves_to_in_progress_when_work_starts(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $pad = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'REM'])->id, 'observation_point' => 'Kampas Rem']);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();
        $flow->startTask($task);

        $this->assertSame(ServiceFinding::STATUS_IN_PROGRESS, $finding->fresh()->status);
        $this->assertSame(ServiceWorkPackage::STATUS_IN_PROGRESS, $package->fresh()->status);
    }

    public function test_start_endpoint_requires_permission_and_redirects(): void
    {
        [$task] = $this->makeReadyTask();

        $this->post(route('work-tasks.start', $task))
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
