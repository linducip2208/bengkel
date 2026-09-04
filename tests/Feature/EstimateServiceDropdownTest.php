<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkQcCheck;
use App\Models\ServiceWorkTask;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;
use Spatie\Permission\Models\Role;

/**
 * The /estimates dropdown: per-state actions, preview data, and the
 * guarantee that the flow never escapes to the generic /services page.
 */
class EstimateServiceDropdownTest extends WorkshopFlowTestCase
{
    public function test_dropdown_modal_wired_on_index_page(): void
    {
        $html = $this->get(route('estimates.index'))->getContent();

        $this->assertStringContainsString('serviceSelectModal', $html);
        $this->assertStringContainsString('Pilih Service / Work Order', $html);
        $this->assertStringContainsString('svcSelectSearch', $html);
        $this->assertStringContainsString('svcQuickFilters', $html);
        $this->assertStringContainsString('Belum Ada Estimasi', $html);
        $this->assertStringContainsString('Semua Service', $html);
        $this->assertStringNotContainsString('Buka Daftar Servis', $html);
    }

    public function test_draft_service_returns_continue_draft_action(): void
    {
        $service = $this->makeService();
        app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'WORK',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        app(EstimateService::class)->createDraft($service, [], [], [
            ServiceWorkPackage::where('service_id', $service->id)->firstOrFail()->id,
        ]);

        $row = collect($this->get(route('estimates.service-search', ['q' => $service->job_no, 'filter' => 'draft']))->json('results'))
            ->firstWhere('id', $service->id);

        $this->assertSame('continue_draft', $row['action']);
        $this->assertSame('Lanjutkan Draft', $row['action_label']);
        // Continue goes to the dedicated builder, never /services.
        $this->assertStringContainsString('/estimates/create', $row['url']);
    }

    public function test_approved_service_returns_revise_action_without_duplicate(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'WORK', 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $row = collect($this->get(route('estimates.service-search', ['q' => $service->job_no, 'filter' => 'all']))->json('results'))
            ->firstWhere('id', $service->id);

        $this->assertSame('revise', $row['action']);
        $this->assertSame('Buat Revisi', $row['action_label']);
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $row['estimate']['status']);

        // Re-opening the builder shows the state card — the approve retry
        // path never creates a competing estimate.
        $this->get(route('estimates.create', ['service_id' => $service->id]))
            ->assertOk()
            ->assertSee('sudah memiliki estimasi terbit');

        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');
        $this->assertSame(1, ServiceEstimate::where('service_id', $service->id)->count());
    }

    public function test_converted_service_returns_view_invoice_action(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'WORK',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');
        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();
        $flow->finishTask($task);
        $flow->submitQc($package->fresh(), ServiceWorkQcCheck::RESULT_PASSED, 'Lulus');
        $invoice = app(EstimateService::class)->convertToInvoice($estimate->fresh());

        $row = collect($this->get(route('estimates.service-search', ['q' => $service->job_no, 'filter' => 'all']))->json('results'))
            ->firstWhere('id', $service->id);

        $this->assertSame('view', $row['action']);
        $this->assertSame('converted', $row['estimate']['status']);
        $this->assertStringContainsString('/invoices/'.$invoice->id, $row['url_view_invoice'] ?? '');
    }

    public function test_preview_endpoint_returns_operational_summary(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $pad = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'REM'])->id, 'observation_point' => 'Kampas Rem']);
        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Habis'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 75000],
        ]);

        $preview = $this->get(route('estimates.service-preview', $service))->assertOk()->json();

        $this->assertSame($service->job_no, $preview['service']['job_no']);
        $this->assertSame($service->customer->name, $preview['service']['customer']);
        $this->assertSame($service->vehicle->number_plate, $preview['service']['plate']);
        // The single critical point was inspected → 1 of 1.
        $this->assertSame(1, $preview['checklist']['checked_count']);
        $this->assertSame(1, $preview['checklist']['total_points']);
        $this->assertSame(1, $preview['findings']['open']);
        $this->assertSame(1, $preview['findings']['critical']);
        $this->assertSame(1, $preview['work_packages']);
        $this->assertNull($preview['latest_estimate']);
        // Primary action routes to the estimate builder — never /services.
        $this->assertStringContainsString('/estimates/create', $preview['primary']['url']);
    }

    public function test_preview_shows_latest_estimate_for_issued_service(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'WORK',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');

        $preview = $this->get(route('estimates.service-preview', $service))->assertOk()->json();

        $this->assertSame($estimate->estimate_number, $preview['latest_estimate']['number']);
        $this->assertSame('waiting_approval', $preview['latest_estimate']['status']);
        $this->assertSame('Lihat Estimasi', $preview['primary']['label']);
    }

    public function test_preview_is_branch_scoped(): void
    {
        $branchA = Branch::create(['name' => 'A', 'code' => 'PA'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'B', 'code' => 'PB'.uniqid(), 'is_active' => true]);

        session(['current_branch_id' => $branchA->id]);
        $serviceA = $this->makeService();
        $serviceA->forceFill(['branch_id' => $branchA->id])->save();

        session(['current_branch_id' => $branchB->id]);
        $this->get(route('estimates.service-preview', $serviceA->id))->assertNotFound();

        session()->forget('current_branch_id');
    }

    public function test_preview_requires_permission(): void
    {
        $service = $this->makeService();
        Role::findOrCreate('no_perm', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('no_perm');
        $this->actingAs($user);

        $this->get(route('estimates.service-preview', $service))->assertForbidden();
    }

    public function test_primary_actions_never_point_to_generic_services_index(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'WORK',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');

        $search = $this->get(route('estimates.service-search', ['q' => $service->job_no]))->json();
        $preview = $this->get(route('estimates.service-preview', $service))->json();

        foreach ($search['results'] as $row) {
            $this->assertStringNotContainsString('/services', $row['url']);
        }
        $this->assertStringNotContainsString('/services', $preview['primary']['url']);
        $this->assertStringContainsString('/estimates/create', $preview['primary']['url']);
    }
}
