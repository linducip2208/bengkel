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
 * Menu Purchases vs Purchase Orders mengikuti permission modul
 * masing-masing (dulu link PO ikut purchase.view → 403).
 */
class PurchaseMenuVisibilityTest extends TestCase
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

    public function test_po_menu_follows_po_permission_not_purchase(): void
    {
        $this->actingAsRoleWithPermissions('po_only', ['purchase-order.view']);
        $html = (string) $this->view('layouts.app');

        $this->assertStringContainsString(route('purchase-orders.index'), $html);
        $this->assertStringNotContainsString(route('purchases.index'), $html);
    }

    public function test_purchase_menu_hidden_from_po_only_user(): void
    {
        $this->actingAsRoleWithPermissions('gudang', ['purchase.view']);
        $html = (string) $this->view('layouts.app');

        $this->assertStringContainsString(route('purchases.index'), $html);
        $this->assertStringNotContainsString(route('purchase-orders.index'), $html);
    }
}
