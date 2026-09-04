<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkQcCheck;
use App\Models\ServiceWorkTask;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * Selecting a Service keeps the user inside the Estimate workflow and
 * shows per-state actions (never silent duplicates).
 */
class EstimateCreateServiceSelectionTest extends WorkshopFlowTestCase
{
    public function test_selecting_service_shows_auto_data_and_workflow_status(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();
        $package = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Kampas', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas Rem', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        $response = $this->get(route('estimates.create', ['service_id' => $service->id]));

        $response->assertOk()
            // Auto customer/vehicle + workflow status + job no
            ->assertSee($service->job_no)
            ->assertSee($service->customer->name)
            ->assertSee($service->vehicle->number_plate)
            ->assertSee('otomatis')
            ->assertSee(Service::WORKFLOW_LABELS[$service->workflow_status])
            // TEMUAN block
            ->assertSee('TEMUAN')
            ->assertSee($finding->finding_number)
            ->assertSee('1.2 mm')
            // WORK PACKAGE block with source badge
            ->assertSee('WORK PACKAGE')
            ->assertSee('GANTI KAMPAS REM')
            ->assertSee('dari '.$finding->finding_number)
            // Save stays in estimate workflow
            ->assertSee('Simpan Draft')
            ->assertSee('name="redirect_to" value="estimates"', false);
    }

    public function test_too_early_service_shows_inspection_notice_but_still_allows_continue(): void
    {
        $service = $this->makeService(['workflow_status' => 1]); // Checked In

        $this->get(route('estimates.create', ['service_id' => $service->id]))
            ->assertOk()
            ->assertSee('Service belum diperiksa.')
            // Builder still available â€” SA may continue intentionally.
            ->assertSee('Simpan Draft');
    }

    public function test_saving_from_builder_redirects_to_estimates_with_success(): void
    {
        $service = $this->makeService();

        $response = $this->post(route('services.estimates.store', $service), [
            'redirect_to' => 'estimates',
            'items' => [
                ['item_type' => 'other', 'description' => 'Item Builder', 'quantity' => 1, 'unit_price' => 50000],
            ],
        ]);

        $response->assertRedirect(route('estimates.index'));
        $response->assertSessionHas('success', function ($message) {
            return str_contains($message, 'berhasil dibuat');
        });
        $response->assertSessionHas('created_estimate_id');
    }

    public function test_approved_estimate_shows_state_card_and_no_builder(): void
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

        $response = $this->get(route('estimates.create', ['service_id' => $service->id]));

        $response->assertOk()
            ->assertSee('sudah memiliki estimasi terbit')
            ->assertSee($estimate->estimate_number)
            ->assertSee('Lihat Estimasi')
            ->assertSee('Buat Revisi')
            // Builder form must NOT render â€” no accidental duplicate.
            ->assertDontSee('Simpan Draft');

        $this->assertSame(1, ServiceEstimate::where('service_id', $service->id)->count());
    }

    public function test_converted_estimate_shows_invoice_link(): void
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

        $this->get(route('estimates.create', ['service_id' => $service->id]))
            ->assertOk()
            ->assertSee('Lihat Invoice')
            ->assertDontSee('Simpan Draft');
    }
}
