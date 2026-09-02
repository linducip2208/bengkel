<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * Dedicated file: QC FAIL must NEVER resolve the source finding — the
 * finding is resolved only after approved work completes AND QC passes.
 */
class QcFailureDoesNotResolveFindingTest extends WorkshopFlowTestCase
{
    public function test_failed_qc_leaves_finding_in_progress_or_open(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $pad = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'REM'])->id, 'observation_point' => 'Kampas Rem']);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS', 'service_finding_id' => $finding->id,
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
        $flow->finishTask($task);

        $flow->submitQc($package->fresh(), 'failed', 'Kampas belum rata', $task->id);

        $finding = $finding->fresh();
        $this->assertNotSame(ServiceFinding::STATUS_RESOLVED, $finding->status);
        $this->assertNull($finding->resolved_at);
        $this->assertNull($finding->resolved_by);

        // Approval alone never resolves a finding either.
        $this->assertNotSame(ServiceFinding::STATUS_RESOLVED, $finding->status, 'Finding is resolved by QC pass only.');
    }

    public function test_manual_resolve_blocked_while_approved_work_exists(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $pad = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'REM'])->id, 'observation_point' => 'Kampas Rem']);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS', 'service_finding_id' => $finding->id,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ]);

        $package->forceFill(['status' => ServiceWorkPackage::STATUS_APPROVED])->save();

        // Someone tries to silently resolve the finding while approved work
        // is in flight — the service blocks it and logs a correction attempt.
        $result = $flow->resolveFinding($finding->fresh(), $service, 'Temuan selesai pada pemeriksaan ulang (checklist OK).');

        $this->assertNotSame(ServiceFinding::STATUS_RESOLVED, $result->status);
        $this->assertNotNull(ActivityLogHasCorrection($service));
    }

    public function test_finding_resolution_after_full_chain(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $pad = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'REM'])->id, 'observation_point' => 'Kampas Rem']);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS', 'service_finding_id' => $finding->id,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();
        $flow->startTask($task->fresh());
        $flow->finishTask($task->fresh());

        // Full audit chain exists before resolution:
        $this->assertDatabaseHas('activity_logs', ['event' => 'finding.created']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'work_package.approved']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'work_task.started']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'work_task.completed']);

        $flow->submitQc($package->fresh(), 'passed', 'OK');

        $this->assertDatabaseHas('activity_logs', ['event' => 'qc.passed']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'finding.resolved']);
        $this->assertSame(ServiceFinding::STATUS_RESOLVED, $finding->fresh()->status);
    }
}

/**
 * Helper: does a correction-blocked log exist for this service?
 */
function ActivityLogHasCorrection($service): ?ActivityLog
{
    return ActivityLog::where('event', 'finding.correction_blocked')
        ->where('subject_id', $service->findings()->first()->id ?? null)
        ->first();
}
