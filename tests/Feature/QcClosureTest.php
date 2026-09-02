<?php

namespace Tests\Feature;

use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkQcCheck;
use App\Models\ServiceWorkTask;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * J. QC fail → Finding remains unresolved.
 * K. QC pass → Finding becomes resolved.
 */
class QcClosureTest extends WorkshopFlowTestCase
{
    protected function makeCompletedPackage(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $pad = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'REM'])->id, 'observation_point' => 'Kampas Rem']);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas hampir habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $estimate = $estimate->fresh();
        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();

        // Mechanic executes the work.
        $flow->startTask($task);
        $flow->finishTask($task);
        $package = $package->fresh();

        return [$service, $finding->fresh(), $package, $task->fresh()];
    }

    public function test_qc_pass_resolves_finding(): void
    {
        [$service, $finding, $package, $task] = $this->makeCompletedPackage();

        // Critical assertion K: QC pass → finding resolved.
        app(WorkshopFlowService::class)->submitQc($package, 'passed', 'Instalasi dicek, pedal normal, road test OK');

        $finding = $finding->fresh();
        $this->assertSame(ServiceFinding::STATUS_RESOLVED, $finding->status);
        $this->assertNotNull($finding->resolved_at);
        $this->assertSame(ServiceWorkPackage::STATUS_QC_PASSED, $package->fresh()->status);
        $this->assertSame(ServiceWorkTask::STATUS_QC_PASSED, $task->fresh()->status);
        $this->assertSame(1, ServiceWorkQcCheck::where('service_work_package_id', $package->id)->count());
    }

    public function test_qc_fail_keeps_finding_unresolved_and_requires_reason(): void
    {
        [$service, $finding, $package, $task] = $this->makeCompletedPackage();

        // FAIL without reason is rejected.
        try {
            app(WorkshopFlowService::class)->submitQc($package, 'failed', null, $task->id);
            $this->fail('FAIL without reason must be rejected.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        // Critical assertion J: QC fail → finding stays unresolved.
        app(WorkshopFlowService::class)->submitQc($package, 'failed', 'Suara abnormal setelah road test', $task->id);

        $finding = $finding->fresh();
        $this->assertNotSame(ServiceFinding::STATUS_RESOLVED, $finding->status);
        $this->assertNull($finding->resolved_at);
        $this->assertSame(ServiceWorkPackage::STATUS_QC_FAILED, $package->fresh()->status);

        // Task reopened for rework — timer may start again.
        $this->assertSame(ServiceWorkTask::STATUS_READY, $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_rework_then_repass_resolves_finding(): void
    {
        [$service, $finding, $package, $task] = $this->makeCompletedPackage();
        $flow = app(WorkshopFlowService::class);

        $flow->submitQc($package, 'failed', 'Perlu pengencangan baut', $task->id);

        // Mechanic reworks and finishes again.
        $flow->startTask($task->fresh());
        $flow->finishTask($task->fresh());
        $flow->submitQc($package->fresh(), 'passed', 'Road test kedua OK', $task->fresh()->id);

        $this->assertSame(ServiceFinding::STATUS_RESOLVED, $finding->fresh()->status);
        $this->assertSame(2, ServiceWorkQcCheck::where('service_work_package_id', $package->id)->count(), 'Both QC attempts recorded.');
        $this->assertSame(ServiceWorkPackage::STATUS_QC_PASSED, $package->fresh()->status);
    }

    public function test_qc_retry_does_not_duplicate_resolution(): void
    {
        [$service, $finding, $package] = $this->makeCompletedPackage();
        $flow = app(WorkshopFlowService::class);

        $flow->submitQc($package, 'passed', 'OK');
        $flow->submitQc($package->fresh(), 'passed', 'OK ulang');

        $finding = $finding->fresh();
        $this->assertSame(ServiceFinding::STATUS_RESOLVED, $finding->status);
        $this->assertNotNull($finding->resolved_at);
    }

    public function test_qc_fail_via_http_requires_reason(): void
    {
        [$service, $finding, $package] = $this->makeCompletedPackage();

        // FAIL via HTTP without notes → 422 (mandatory reason) and nothing recorded.
        $checksBefore = ServiceWorkQcCheck::count();
        $this->from('/')->post(route('work-packages.qc.store', $package), [
            'result' => 'failed',
            'notes' => '',
        ])->assertStatus(422);

        $this->assertSame($checksBefore, ServiceWorkQcCheck::count());
    }

    public function test_qc_page_renders_for_authorized_user(): void
    {
        [$service, $finding, $package] = $this->makeCompletedPackage();

        $this->get(route('work-packages.qc', $service))->assertOk();
    }
}
