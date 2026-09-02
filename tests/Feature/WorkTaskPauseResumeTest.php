<?php

namespace Tests\Feature;

use App\Models\ServiceWorkTask;
use App\Models\ServiceWorkTimeEntry;
use App\Services\EstimateService;
use App\Services\WorkshopFlowService;

/**
 * Pause/resume in one focused file (alias scenario of WorkTaskTimerTest).
 */
class WorkTaskPauseResumeTest extends WorkshopFlowTestCase
{
    public function test_pause_excludes_paused_duration_from_actual(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'TUNE UP', 'standard_minutes' => 60,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Tune Up', 'quantity' => 1, 'unit_price' => 150000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();

        // Work 15 min Ã¢â€ â€™ pause 40 min Ã¢â€ â€™ resume Ã¢â€ â€™ work 10 min.
        $flow->startTask($task);
        $this->travel(15)->minutes();
        $flow->pauseTask($task);
        $this->travel(40)->minutes();
        $flow->startTask($task);
        $this->travel(10)->minutes();
        $flow->finishTask($task);
        $this->travelBack();

        $task = $task->fresh();
        // Actual = 25 min of work Ã¢â‚¬â€ the 40-minute pause is excluded.
        $this->assertSame(25, $task->actualMinutes());
        // Standard remains 60.
        $this->assertSame(60, $task->standard_minutes);
        $this->assertSame(2, $task->timeEntries()->count(), 'Two work intervals recorded.');
    }

    public function test_resume_reuses_start_route(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'WORK', 'standard_minutes' => 20,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();
        $flow->startTask($task);
        $flow->pauseTask($task);

        // Resume over HTTP (the same start route).
        $this->post(route('work-tasks.start', $task))->assertRedirect();

        $this->assertSame(ServiceWorkTask::STATUS_IN_PROGRESS, $task->fresh()->status);
        $this->assertSame(2, ServiceWorkTimeEntry::where('service_work_task_id', $task->id)->count());
    }
}
