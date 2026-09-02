<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Sidebar visibility of the dedicated Estimasi menu entry.
 */
class EstimateMenuVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);
    }

    private function actingAsRoleWithPermissions(string $role, array $permissions): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }
        $this->actingAs($user);

        return $user;
    }

    private function seeEstimateMenu(bool $expected): void
    {
        $html = (string) $this->view('layouts.app');

        if ($expected) {
            $this->assertStringContainsString(route('estimates.index'), $html);
            $this->assertStringContainsString('Estimasi', $html);
            $this->assertStringContainsString('fa-file-signature', $html);
        } else {
            $this->assertStringNotContainsString(route('estimates.index'), $html);
        }
    }

    public function test_user_with_estimates_view_sees_sidebar_menu(): void
    {
        $this->actingAsRoleWithPermissions('service_advisor', ['estimates.view', 'service.view', 'service-package.view']);

        $this->seeEstimateMenu(true);
    }

    public function test_user_without_estimates_view_does_not_see_menu(): void
    {
        $this->actingAsRoleWithPermissions('tanpa_estimasi', ['dashboard.view']);

        $this->seeEstimateMenu(false);
    }

    public function test_menu_stays_expanded_on_estimate_index(): void
    {
        $this->actingAsRoleWithPermissions('service_advisor', ['estimates.view', 'service.view']);

        // On /estimates the Service Management submenu must render expanded.
        $response = $this->get(route('estimates.index'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('id="menuServiceMgmt"', $html);
        $this->assertMatchesRegularExpression('/collapse submenu [^"]*show[^"]*"\s+id="menuServiceMgmt"/', $html);
        $this->assertStringContainsString('aria-expanded="true"', $html);
    }

    public function test_service_management_group_grants_on_estimates_view_alone(): void
    {
        // A user who ONLY holds estimates.view must still render the Service
        // Management group (the menu must be reachable).
        $this->actingAsRoleWithPermissions('estimasi_only', ['estimates.view']);

        $html = (string) $this->view('layouts.app');
        $this->assertStringContainsString('menuServiceMgmt', $html);
        $this->assertStringContainsString(route('estimates.index'), $html);
    }

    public function test_menu_route_resolves_to_index_page(): void
    {
        $this->actingAsRoleWithPermissions('service_advisor', ['estimates.view', 'service.view']);

        $this->get(route('estimates.index'))->assertOk();
    }
}
