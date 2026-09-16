<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Server-side authorization: hiding menu items is not enough — sensitive
 * write endpoints must reject unauthorized roles with 403.
 */
class AuthorizationTest extends TestCase
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

    private function actingAsRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    public function test_mechanic_cannot_create_users(): void
    {
        $this->actingAsRole('mekanik');

        $response = $this->post('/users', [
            'name' => 'Hacker',
            'email' => 'hacker@bengkel.test',
            'password' => 'secret123',
            'role' => 'super_admin',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'hacker@bengkel.test']);
    }

    public function test_cashier_cannot_update_roles(): void
    {
        $role = Role::findOrCreate('kasir', 'web');
        $this->actingAsRole('kasir');

        $response = $this->put("/roles/{$role->id}", [
            'name' => 'kasir-palsu',
        ]);

        $response->assertForbidden();
    }

    public function test_cashier_cannot_export_financial_reports(): void
    {
        $this->actingAsRole('kasir');

        $response = $this->get('/reports/export-pdf?type=profit-loss');

        $response->assertForbidden();
    }

    public function test_admin_can_export_financial_reports(): void
    {
        $this->actingAsRole('admin');

        // The PDF view may not exist for every type; we only assert the
        // authorization layer lets admins through (not a 403).
        $response = $this->get('/reports/export-pdf?type=sales');

        $this->assertNotEquals(403, $response->status());
    }

    public function test_mechanic_cannot_approve_stock_adjustments(): void
    {
        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Produk Adj',
            'product_type_id' => ProductType::create(['type' => 'T'.uniqid(), 'slug' => uniqid(), 'is_active' => true])->id,
            'unit_id' => ProductUnit::create(['name' => 'N'.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true])->id,
            'price' => 10000,
        ]);

        $this->actingAsRole('mekanik');

        $adjustment = StockAdjustment::create([
            'product_id' => $product->id,
            'branch_id' => Branch::create(['name' => 'Cabang Uji '.uniqid(), 'is_active' => true])->id,
            'previous_quantity' => 0,
            'new_quantity' => 5,
            'quantity_change' => 5,
            'reason' => 'uji',
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);
        if (! $adjustment->exists) {
            $this->markTestSkipped('stock_adjustments schema mismatch.');
        }

        $response = $this->post("/stock-adjustments/{$adjustment->id}/approve", []);

        $response->assertForbidden();
        $this->assertEquals('pending', $adjustment->fresh()->status);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['email' => 'off@bengkel.test', 'is_active' => false]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'email' => 'off@bengkel.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_admin_can_open_user_edit_modal_without_transparent_table_nesting(): void
    {
        $admin = $this->actingAsRole('admin');
        $targetRole = Role::findOrCreate('kasir', 'web');
        $target = User::factory()->create(['name' => 'Kasir Uji']);
        $target->assignRole($targetRole);

        $response = $this->get('/users');

        $response->assertOk();
        $response->assertSee('id="editUserModal'.$target->id.'"', false);
        $response->assertSee('class="btn btn-outline-secondary password-toggle"', false);
        $response->assertSee('type="button" class="btn-close"', false);
    }

    public function test_admin_can_update_another_users_password(): void
    {
        $admin = $this->actingAsRole('admin');
        $permission = Permission::findOrCreate('users.manage', 'web');
        $admin->givePermissionTo($permission);

        $targetRole = Role::findOrCreate('kasir', 'web');
        $target = User::factory()->create([
            'name' => 'Kasir Lama',
            'email' => 'kasir-lama@bengkel.test',
            'password' => 'password-lama',
            'is_active' => true,
        ]);
        $target->assignRole($targetRole);

        $response = $this->put("/users/{$target->id}", [
            'name' => 'Kasir Baru',
            'email' => 'kasir-baru@bengkel.test',
            'password' => 'password-baru',
            'role' => 'kasir',
            'is_active' => '1',
            '_form' => 'edit-user',
            'editing_user_id' => $target->id,
        ]);

        $response->assertRedirect();
        $target->refresh();

        $this->assertSame('Kasir Baru', $target->name);
        $this->assertSame('kasir-baru@bengkel.test', $target->email);
        $this->assertTrue(Hash::check('password-baru', $target->password));
    }
}
