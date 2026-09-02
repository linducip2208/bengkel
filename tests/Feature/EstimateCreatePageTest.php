<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * /estimates/create page: loading, permissions, no DB mutation.
 */
class EstimateCreatePageTest extends WorkshopFlowTestCase
{
    public function test_create_page_loads_and_never_mutates_db(): void
    {
        $serviceCountBefore = Service::count();
        $estimateCountBefore = ServiceEstimate::count();

        $this->get(route('estimates.create'))
            ->assertOk()
            ->assertSee('Buat Estimasi Servis')
            ->assertSee('Pilih Service / Work Order');

        // Opening the page must NOT create an Estimate row.
        $this->assertSame($serviceCountBefore, Service::count());
        $this->assertSame($estimateCountBefore, ServiceEstimate::count());
    }

    public function test_create_page_requires_estimates_create_permission(): void
    {
        Role::findOrCreate('viewer_only', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('viewer_only');
        Permission::findOrCreate('estimates.view', 'web');
        $user->givePermissionTo('estimates.view');
        $this->actingAs($user);

        $this->get(route('estimates.create'))->assertForbidden();
        $this->get(route('estimates.service-search'))->assertForbidden();
    }

    public function test_eligible_services_are_searchable_by_all_criteria(): void
    {
        $service = $this->makeService();
        $service->refresh();
        $other = $this->makeService();
        $other->refresh();

        // job_no
        $ids = collect($this->get(route('estimates.service-search', ['q' => $service->job_no]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));

        // customer name
        $ids = collect($this->get(route('estimates.service-search', ['q' => $service->customer->name]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));
        $this->assertFalse($ids->contains($other->id));

        // customer phone
        $ids = collect($this->get(route('estimates.service-search', ['q' => $service->customer->phone]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));

        // vehicle plate (partial)
        $ids = collect($this->get(route('estimates.service-search', ['q' => substr($service->vehicle->number_plate, 0, 3)]))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($service->id));
    }

    public function test_completed_services_are_excluded_from_search(): void
    {
        $completed = $this->makeService();
        $completed->update(['workflow_status' => 12]);
        $active = $this->makeService();

        $ids = collect($this->get(route('estimates.service-search'))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($completed->id), 'Completed service: new estimate = new Service.');
    }

    public function test_index_create_button_links_to_create_page_never_to_services(): void
    {
        $response = $this->get(route('estimates.index'));
        $response->assertOk();

        $html = $response->getContent();
        // The create flow stays inside Estimasi: the searchable dropdown is
        // wired to the service-search endpoint and the primary action routes
        // to the dedicated builder — never to /services.
        $this->assertStringContainsString('/estimates/service-search', $html);
        $this->assertStringContainsString('Pilih Service / Work Order', $html);
        $this->assertStringContainsString('svcSelectResults', $html);
        // The old "Buka Daftar Servis" fallback is gone.
        $this->assertStringNotContainsString('Buka Daftar Servis', $html);
    }

    public function test_branch_isolation_is_enforced_on_selection(): void
    {
        $branchA = Branch::create(['name' => 'Cabang A', 'code' => 'BA'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Cabang B', 'code' => 'BB'.uniqid(), 'is_active' => true]);

        session(['current_branch_id' => $branchA->id]);
        $serviceA = $this->makeService();
        $serviceA->forceFill(['branch_id' => $branchA->id])->save();

        session(['current_branch_id' => $branchB->id]);
        $serviceB = $this->makeService();
        $serviceB->forceFill(['branch_id' => $branchB->id])->save();

        // Branch-B user cannot open branch-A service â†’ stays at search step.
        $this->get(route('estimates.create', ['service_id' => $serviceA->id]))
            ->assertRedirect(route('estimates.create'))
            ->assertSessionHas('error');

        // Branch-B service opens fine.
        $this->get(route('estimates.create', ['service_id' => $serviceB->id]))->assertOk();

        // Search under branch B never leaks branch A services.
        $ids = collect($this->get(route('estimates.service-search'))->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($serviceB->id));
        $this->assertFalse($ids->contains($serviceA->id));
    }
}
