<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkQcCheck;
use App\Models\ServiceWorkTask;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * §26 — Work discovered mid-job becomes an ADDITIONAL finding → work
 * package → revision/additional estimate → customer approval. No
 * unauthorized surprise charges on the invoice.
 */
class AdditionalWorkRequiresApprovalTest extends WorkshopFlowTestCase
{
    protected function finishAndPass(ServiceWorkPackage $package): void
    {
        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();
        app(WorkshopFlowService::class)->finishTask($task);
        app(WorkshopFlowService::class)->submitQc($package->fresh(), ServiceWorkQcCheck::RESULT_PASSED, 'Lulus');
    }

    protected function makeApprovedRunningEstimate(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $pad = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'REM'])->id, 'observation_point' => 'Kampas Rem']);
        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Kampas', 'quantity' => 1, 'unit_price' => 75000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();
        $flow->startTask($task); // work in progress

        return [$service, $flow, $estimate->fresh(), $package];
    }

    public function test_new_finding_during_work_stays_open_without_price(): void
    {
        [$service, $flow] = $this->makeApprovedRunningEstimate();

        // Mechanic discovers a leaking rack steering boot mid-job.
        $boot = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'STEERING'])->id, 'observation_point' => 'Boot Rack Steering']);
        app(ObservationService::class)->saveCheckResults($service, [
            $boot->id => ['condition_status' => 'repair_required', 'comment' => 'Boot bocor, grease hilang'],
        ]);

        $additional = ServiceFinding::where('service_id', $service->id)->where('severity', 'repair_required')->firstOrFail();
        $this->assertNotSame(ServiceFinding::STATUS_RESOLVED, $additional->status);
        $this->assertArrayNotHasKey('unit_price', $additional->getAttributes(), 'Finding is technical, never priced.');
    }

    public function test_additional_work_package_does_not_enter_estimate_automatically(): void
    {
        [$service, $flow, $estimate, $originalPackage] = $this->makeApprovedRunningEstimate();

        $boot = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'STEERING'])->id, 'observation_point' => 'Boot Rack Steering']);
        app(ObservationService::class)->saveCheckResults($service, [
            $boot->id => ['condition_status' => 'repair_required', 'comment' => 'Boot bocor'],
        ]);
        $additional = ServiceFinding::where('service_id', $service->id)->where('severity', 'repair_required')->firstOrFail();

        $additionalPackage = $flow->saveWorkPackage($service, [
            'title' => 'PERBAIKI BOOT RACK STEERING', 'service_finding_id' => $additional->id, 'standard_minutes' => 45,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Rack Steering', 'quantity' => 1, 'unit_price' => 200000],
            ['item_type' => 'part', 'description' => 'Boot Rack', 'quantity' => 1, 'unit_price' => 350000],
        ]);

        // The RUNNING approved estimate is untouched — its task list unchanged,
        // its groups unchanged. New work exists only as a draft package.
        $estimate = $estimate->fresh();
        $this->assertSame(1, $estimate->groups()->count(), 'Running estimate never gains unapproved work.');
        $this->assertSame(1, ServiceWorkTask::count(), 'No task for unapproved additional work.');
        $this->assertSame(ServiceWorkPackage::STATUS_DRAFT, $additionalPackage->status);
    }

    public function test_additional_work_reaches_execution_only_after_new_approval(): void
    {
        [$service, $flow, $estimate, $originalPackage] = $this->makeApprovedRunningEstimate();

        $boot = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'STEERING'])->id, 'observation_point' => 'Boot Rack Steering']);
        app(ObservationService::class)->saveCheckResults($service, [
            $boot->id => ['condition_status' => 'repair_required'],
        ]);
        $additional = ServiceFinding::where('service_id', $service->id)->where('severity', 'repair_required')->firstOrFail();

        $additionalPackage = $flow->saveWorkPackage($service, [
            'title' => 'PERBAIKI BOOT RACK STEERING', 'service_finding_id' => $additional->id, 'standard_minutes' => 45,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Rack', 'quantity' => 1, 'unit_price' => 200000],
            ['item_type' => 'part', 'description' => 'Boot Rack', 'quantity' => 1, 'unit_price' => 350000],
        ]);

        // Draft → proposed on the RUNNING estimate would be immutable; the
        // additional work flows through a REVISION (new document version):
        // the revision draft gains the additional package as a new group.
        $revision = app(EstimateService::class)->revise($estimate, [], [], 'Menemukan kebocoran boot rack steering');
        $revision = $revision->fresh();
        $flow->addWorkPackageToEstimate($revision, $additionalPackage);

        // Revision carries BOTH packages as pending groups.
        $this->assertSame(2, $revision->groups()->count());
        $revision->groups()->update(['customer_decision' => 'approved', 'decided_at' => now()]);
        app(EstimateService::class)->recalculateApprovedAmounts($revision);
        $revision->forceFill(['status' => ServiceEstimate::STATUS_APPROVED, 'decision_status' => ServiceEstimate::DECISION_APPROVED])->save();

        app(EstimateService::class)->recalculateApprovedAmounts($revision);
        $flow->createTasksForApprovedGroups($revision);

        // NOW both works have tasks — original + additional.
        $this->assertSame(2, ServiceWorkTask::count());
        $this->assertNotNull(ServiceWorkTask::where('service_work_package_id', $additionalPackage->id)->first());
        $this->assertNotNull(ServiceWorkTask::where('service_work_package_id', $originalPackage->id)->first());
    }

    public function test_unapproved_additional_work_never_reaches_invoice(): void
    {
        [$service, $flow, $estimate] = $this->makeApprovedRunningEstimate();

        $boot = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'STEERING'])->id, 'observation_point' => 'Boot Rack Steering']);
        app(ObservationService::class)->saveCheckResults($service, [
            $boot->id => ['condition_status' => 'repair_required'],
        ]);
        $additional = ServiceFinding::where('service_id', $service->id)->where('severity', 'repair_required')->firstOrFail();

        // Draft additional package exists but is NEVER approved/attached.
        $flow->saveWorkPackage($service, [
            'title' => 'PERBAIKI BOOT RACK STEERING', 'service_finding_id' => $additional->id,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Rack', 'quantity' => 1, 'unit_price' => 200000],
        ]);

        $this->finishAndPass($estimate->groups()->firstOrFail()->workPackage);
        $invoice = app(EstimateService::class)->convertToInvoice($estimate->fresh());
        $invoice->load('items');

        // Invoice contains ONLY the approved original work (Rp 75.000).
        $this->assertEqualsWithDelta(75000.0, (float) $invoice->grand_total, 0.01);
        $this->assertNull($invoice->items->firstWhere('description', 'Jasa Rack'), 'Surprise charge must never appear on the invoice.');
        $this->assertSame(1, $invoice->items->count());
    }

    public function test_partial_approval_of_revision_invoices_approved_work_only(): void
    {
        [$service, $flow, $estimate, $originalPackage] = $this->makeApprovedRunningEstimate();

        $boot = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'STEERING'])->id, 'observation_point' => 'Boot Rack Steering']);
        app(ObservationService::class)->saveCheckResults($service, [
            $boot->id => ['condition_status' => 'repair_required'],
        ]);
        $additional = ServiceFinding::where('service_id', $service->id)->where('severity', 'repair_required')->firstOrFail();
        $additionalPackage = $flow->saveWorkPackage($service, [
            'title' => 'PERBAIKI BOOT RACK STEERING', 'service_finding_id' => $additional->id, 'standard_minutes' => 45,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Rack', 'quantity' => 1, 'unit_price' => 200000],
            ['item_type' => 'part', 'description' => 'Boot Rack', 'quantity' => 1, 'unit_price' => 350000],
        ]);

        // Revision: original carried over + additional package attached as a
        // new group; customer approves original, REJECTS the additional work.
        $revision = app(EstimateService::class)->revise($estimate, [], [], 'Additional work ditemukan');
        $revision = $revision->fresh();
        $flow->addWorkPackageToEstimate($revision, $additionalPackage);
        $revision->groups()->where('title', 'GANTI KAMPAS REM')->update(['customer_decision' => 'approved', 'decided_at' => now()]);
        $revision->groups()->where('title', 'PERBAIKI BOOT RACK STEERING')->update(['customer_decision' => 'rejected', 'decided_at' => now()]);
        app(EstimateService::class)->recalculateApprovedAmounts($revision);
        $revision->forceFill(['status' => ServiceEstimate::STATUS_PARTIALLY_APPROVED, 'decision_status' => ServiceEstimate::DECISION_PARTIALLY_APPROVED])->save();
        app(EstimateService::class)->recalculateApprovedAmounts($revision);

        $flow->createTasksForApprovedGroups($revision->fresh());

        $this->finishAndPass($originalPackage);

        // Only the original work got a task.
        $this->assertSame(1, ServiceWorkTask::count());
        $this->assertNull(ServiceWorkTask::where('service_work_package_id', $additionalPackage->id)->first());

        $invoice = app(EstimateService::class)->convertToInvoice($revision->fresh());
        $invoice->load('items');
        $this->assertEqualsWithDelta(75000.0, (float) $invoice->grand_total, 0.01);
        $this->assertNull($invoice->items->firstWhere('description', 'Jasa Rack'));
    }
}
