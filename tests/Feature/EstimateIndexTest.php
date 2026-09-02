<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ServiceEstimate;
use App\Services\EstimateService;
use Spatie\Permission\Models\Role;

/**
 * Central estimate management page: /estimates (menu Estimasi).
 */
class EstimateIndexTest extends EstimateTestCase
{
    public function test_index_route_loads_and_lists_estimates(): void
    {
        $estimate = $this->issueEstimate($this->makeService());

        $response = $this->get(route('estimates.index'));

        $response->assertOk();
        $response->assertSee($estimate->estimate_number);
        $response->assertSee('Estimasi Servis');
        $response->assertSee('Buat Estimasi');
    }

    public function test_index_requires_estimates_view_permission(): void
    {
        Role::findOrCreate('tanpa_akses', 'web');
        $user = $this->makeUser('tanpa_akses');

        $this->actingAs($user)->get(route('estimates.index'))->assertForbidden();
    }

    public function test_search_matches_estimate_number_job_no_customer_and_plate(): void
    {
        $estimate = $this->issueEstimate($this->makeService());
        $other = $this->issueEstimate($this->makeService());

        $this->get(route('estimates.index', ['search' => $estimate->estimate_number]))
            ->assertOk()
            ->assertSee($estimate->estimate_number)
            ->assertDontSee($other->estimate_number);

        $this->get(route('estimates.index', ['search' => $estimate->service->job_no]))
            ->assertOk()
            ->assertSee($estimate->estimate_number)
            ->assertDontSee($other->estimate_number);

        $this->get(route('estimates.index', ['search' => $estimate->customer->name]))
            ->assertOk()
            ->assertSee($estimate->estimate_number)
            ->assertDontSee($other->estimate_number);

        $this->get(route('estimates.index', ['search' => $estimate->vehicle->number_plate]))
            ->assertOk()
            ->assertSee($estimate->estimate_number)
            ->assertDontSee($other->estimate_number);
    }

    public function test_status_filter_narrows_results(): void
    {
        $draftService = $this->makeService();
        $draft = app(EstimateService::class)->createDraft($draftService, [], [$this->itemPayload()]);

        $issued = $this->issueEstimate($this->makeService());

        $this->get(route('estimates.index', ['status' => ServiceEstimate::STATUS_DRAFT]))
            ->assertOk()
            ->assertSee($draft->estimate_number)
            ->assertDontSee($issued->estimate_number);

        $this->get(route('estimates.index', ['status' => ServiceEstimate::STATUS_WAITING_APPROVAL]))
            ->assertOk()
            ->assertSee($issued->estimate_number)
            ->assertDontSee($draft->estimate_number);
    }

    public function test_date_filters_narrow_results(): void
    {
        $today = $this->issueEstimate($this->makeService(), [$this->itemPayload()]);
        $today->forceFill(['estimate_date' => now()->toDateString()])->save();

        $old = $this->issueEstimate($this->makeService(), [$this->itemPayload()]);
        $old->forceFill(['estimate_date' => now()->subDays(30)->toDateString()])->save();

        $this->get(route('estimates.index', [
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk()
            ->assertSee($today->estimate_number)
            ->assertDontSee($old->estimate_number);
    }

    public function test_valid_until_and_version_filters_work(): void
    {
        $estimate = $this->issueEstimate($this->makeService(), [$this->itemPayload()]);

        $this->get(route('estimates.index', ['valid_until' => $estimate->valid_until->toDateString()]))
            ->assertOk()
            ->assertSee($estimate->estimate_number);

        $this->get(route('estimates.index', ['version' => 2]))
            ->assertOk()
            ->assertDontSee($estimate->estimate_number);

        $this->get(route('estimates.index', ['version' => 1]))
            ->assertOk()
            ->assertSee($estimate->estimate_number);
    }

    public function test_branch_isolation_and_branch_filter(): void
    {
        $branchA = Branch::create(['name' => 'Cabang A', 'code' => 'BRA'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Cabang B', 'code' => 'BRB'.uniqid(), 'is_active' => true]);

        session(['current_branch_id' => $branchA->id]);
        $inA = $this->issueEstimate($this->makeService(), [$this->itemPayload()]);
        $inA->forceFill(['branch_id' => $branchA->id])->save();

        $inB = $this->issueEstimate($this->makeService(), [$this->itemPayload()]);
        $inB->forceFill(['branch_id' => $branchB->id])->save();

        // Session context: only branch A rows are visible.
        $this->get(route('estimates.index'))
            ->assertOk()
            ->assertSee($inA->estimate_number)
            ->assertDontSee($inB->estimate_number);

        // Branch filter narrows explicitly (super admin context, unrestricted).
        session()->forget('current_branch_id');
        $this->get(route('estimates.index', ['branch_id' => $branchB->id]))
            ->assertOk()
            ->assertSee($inB->estimate_number)
            ->assertDontSee($inA->estimate_number);

        $this->get(route('estimates.index', ['branch_id' => $branchA->id]))
            ->assertOk()
            ->assertSee($inA->estimate_number);
    }

    public function test_index_paginates_fifteen_per_page(): void
    {
        $estimateService = app(EstimateService::class);

        // 18 distinct services → one draft each (createDraft is idempotent
        // per service, so extra drafts for the same service would update in place).
        for ($i = 0; $i < 18; $i++) {
            $svc = $this->makeService();
            $estimateService->createDraft($svc, [], [$this->itemPayload(['description' => 'ITEM '.$i])]);
        }
        $this->assertSame(18, ServiceEstimate::count());

        $response = $this->get(route('estimates.index'));
        $response->assertOk();
        $this->assertCount(15, $response->viewData('estimates')->items());

        $page2 = $this->get(route('estimates.index', ['page' => 2]));
        $page2->assertOk();
        $this->assertCount(3, $page2->viewData('estimates')->items());
    }

    public function test_revisions_are_listed_individually_without_duplication(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $v1 = $this->issueEstimate($service, [$this->itemPayload()]);
        $estimateService->approve($v1, 'public_link');
        $v2 = $estimateService->revise($v1, [], [$this->itemPayload()], 'uji revisi');

        $this->assertSame(2, ServiceEstimate::count());

        $response = $this->get(route('estimates.index'));

        $response->assertOk()
            ->assertSee($v1->estimate_number)
            ->assertSee($v2->estimate_number);

        // Every row on the page corresponds to a distinct DB row.
        $listed = collect($response->viewData('estimates')->items());
        $this->assertCount(2, $listed);
        $this->assertCount(2, $listed->pluck('id')->unique());

        // The revision is marked (version 2, linked to its predecessor).
        $revisionRow = $listed->firstWhere('id', $v2->id);
        $this->assertSame(2, $revisionRow->version);
        $this->assertSame($v1->id, $revisionRow->previous_estimate_id);
    }

    public function test_create_button_flow_does_not_create_estimates(): void
    {
        $service = $this->makeService();

        // Rendering the index (with the chooser) must not create any estimate.
        $this->get(route('estimates.index'))->assertOk();

        $this->assertSame(0, ServiceEstimate::count());
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_converted_estimate_shows_invoice_link(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service, [$this->itemPayload()]);
        $estimateService->approve($estimate, 'public_link');
        $invoice = $estimateService->convertToInvoice($estimate);

        $this->get(route('estimates.index', ['status' => ServiceEstimate::STATUS_CONVERTED]))
            ->assertOk()
            ->assertSee($estimate->estimate_number)
            ->assertSee(route('invoices.show', $invoice));
    }
}
