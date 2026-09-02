<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ServiceWorkPackage;
use App\Models\User;
use App\Services\WorkshopFlowService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * /estimates/service-search endpoint: searchable, filterable, paginated,
 * branch-scoped, permission-guarded source for the estimate dropdown.
 */
class EstimateServiceSearchTest extends WorkshopFlowTestCase
{
    public function test_find_by_job_no(): void
    {
        $service = $this->makeService();
        $other = $this->makeService();

        $ids = collect($this->get(route('estimates.service-search', ['q' => $service->job_no]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_find_by_customer_name(): void
    {
        $service = $this->makeService();
        $service->refresh();

        $ids = collect($this->get(route('estimates.service-search', ['q' => $service->customer->name]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));
    }

    public function test_find_by_customer_phone(): void
    {
        $service = $this->makeService();
        $service->refresh();

        $ids = collect($this->get(route('estimates.service-search', ['q' => $service->customer->phone]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));
    }

    public function test_find_by_plate(): void
    {
        $service = $this->makeService();
        $service->refresh();

        $ids = collect($this->get(route('estimates.service-search', ['q' => $service->vehicle->number_plate]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));
    }

    public function test_active_service_outside_workflow_2_3_is_not_silently_lost(): void
    {
        // Workflow 1 (Checked In) dan 5 (In Progress) harus tetap muncul.
        $checkedIn = $this->makeService(['workflow_status' => 1]);
        $inProgress = $this->makeService(['workflow_status' => 5]);

        $ids = collect($this->get(route('estimates.service-search', ['filter' => 'all']))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($checkedIn->id));
        $this->assertTrue($ids->contains($inProgress->id));

        $checkedInRow = collect($this->get(route('estimates.service-search', ['q' => $checkedIn->job_no]))->json('results'))->firstWhere('id', $checkedIn->id);
        $this->assertTrue($checkedInRow['needs_inspection'], 'Pre-inspection services are selectable but flagged.');
    }

    public function test_completed_service_is_excluded(): void
    {
        $completed = $this->makeService();
        $completed->update(['workflow_status' => 12]);
        $invoiced = $this->makeService();
        $invoiced->update(['workflow_status' => 9]);
        $active = $this->makeService();

        $ids = collect($this->get(route('estimates.service-search', ['filter' => 'all']))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($completed->id));
        $this->assertFalse($ids->contains($invoiced->id));
    }

    public function test_cancelled_service_is_excluded(): void
    {
        $cancelled = $this->makeService();
        $cancelled->update(['cancelled_at' => now()]);

        $ids = collect($this->get(route('estimates.service-search', ['filter' => 'all']))->json('results'))->pluck('id');
        $this->assertFalse($ids->contains($cancelled->id));
    }

    public function test_branch_isolation_is_respected(): void
    {
        $branchA = Branch::create(['name' => 'Cabang A', 'code' => 'SA'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Cabang B', 'code' => 'SB'.uniqid(), 'is_active' => true]);

        session(['current_branch_id' => $branchA->id]);
        $inA = $this->makeService();
        $inA->forceFill(['branch_id' => $branchA->id])->save();

        session(['current_branch_id' => $branchB->id]);
        $inB = $this->makeService();
        $inB->forceFill(['branch_id' => $branchB->id])->save();

        $ids = collect($this->get(route('estimates.service-search', ['filter' => 'all']))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($inB->id));
        $this->assertFalse($ids->contains($inA->id), 'Branch-A service must never leak to branch-B context.');

        session()->forget('current_branch_id');
    }

    public function test_unauthorized_user_gets_403(): void
    {
        Role::findOrCreate('no_est_create', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('no_est_create');
        $this->actingAs($user);

        $this->get(route('estimates.service-search'))->assertForbidden();
    }

    public function test_ajax_pagination_works(): void
    {
        $flow = app(WorkshopFlowService::class);

        // 25 eligible services.
        for ($i = 0; $i < 25; $i++) {
            $flow->saveWorkPackage($this->makeService(), [
                'title' => 'WP '.$i,
            ], [
                ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 10000],
            ]);
        }
        $this->assertSame(25, ServiceWorkPackage::count());

        $page1 = $this->get(route('estimates.service-search', ['filter' => 'all', 'page' => 1]))->json();
        $page2 = $this->get(route('estimates.service-search', ['filter' => 'all', 'page' => 2]))->json();

        $this->assertCount(20, $page1['results']);
        $this->assertTrue($page1['pagination']['more']);
        $this->assertCount(5, $page2['results']);
        $this->assertFalse($page2['pagination']['more']);

        // No overlap between pages.
        $ids1 = collect($page1['results'])->pluck('id');
        $ids2 = collect($page2['results'])->pluck('id');
        $this->assertSame(0, $ids1->intersect($ids2)->count());
    }

    public function test_search_matches_vehicle_model(): void
    {
        $service = $this->makeService();
        $service->refresh();

        $ids = collect($this->get(route('estimates.service-search', ['q' => $service->vehicle->model_name]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));
    }

    public function test_db_count_is_not_mutated_by_search(): void
    {
        $before = DB::table('services')->count();
        $this->get(route('estimates.service-search', ['q' => 'apapun']));
        $this->assertSame($before, DB::table('services')->count());
    }
}
