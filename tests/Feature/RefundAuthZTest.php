<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Kunci otorisasi modul retur (refund) yang sebelumnya tanpa test khusus:
 * retur jual butuh sale.view, retur beli butuh purchase.view.
 */
class RefundAuthZTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);
    }

    private function actingAsRole(string $role, array $permissions = []): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $models = [];
        foreach ($permissions as $permission) {
            $models[] = Permission::findOrCreate($permission, 'web');
        }
        if ($models) {
            $user->givePermissionTo($models);
        }
        $branch = Branch::create(['name' => 'Cabang Uji '.uniqid(), 'code' => 'T'.uniqid(), 'is_active' => true]);
        $user->branches()->sync([$branch->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_sell_return_index_requires_sale_view(): void
    {
        $this->actingAsRole('custom_viewer', []);

        $this->get(route('sell-returns.index'))->assertForbidden();

        $this->actingAsRole('custom_kasir', ['sale.view']);

        $this->get(route('sell-returns.index'))->assertOk();
    }

    public function test_purchase_return_index_requires_purchase_view(): void
    {
        $this->actingAsRole('custom_viewer', []);

        $this->get(route('purchases.return.index'))->assertForbidden();

        $this->actingAsRole('custom_gudang', ['purchase.view']);

        $this->get(route('purchases.return.index'))->assertOk();
    }
}
