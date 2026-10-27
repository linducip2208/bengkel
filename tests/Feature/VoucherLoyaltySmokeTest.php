<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke test modul Voucher & Loyalty adjust (sebelumnya nol test):
 * CRUD voucher + validasi kode + penyesuaian poin + gate permission.
 */
class VoucherLoyaltySmokeTest extends TestCase
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

    public function test_voucher_crud_and_validate_code(): void
    {
        $this->actingAsRole('custom_admin', ['voucher.view', 'voucher.create', 'voucher.edit', 'voucher.delete', 'pos.view']);

        $this->post(route('vouchers.store'), [
            'code' => 'HEMAT10',
            'name' => 'Hemat 10rb',
            'type' => 'fixed',
            'value' => 10000,
            'min_purchase' => 50000,
        ])->assertRedirect();
        $voucher = Voucher::where('code', 'HEMAT10')->firstOrFail();

        // Kode tak dikenal.
        $this->postJson(route('vouchers.validate'), ['code' => 'SALAH', 'subtotal' => 100000])
            ->assertOk()->assertJson(['ok' => false]);

        // Di bawah minimum belanja.
        $this->postJson(route('vouchers.validate'), ['code' => 'HEMAT10', 'subtotal' => 10000])
            ->assertOk()->assertJson(['ok' => false]);

        // Valid: diskon fixed 10rb.
        $this->postJson(route('vouchers.validate'), ['code' => 'HEMAT10', 'subtotal' => 100000])
            ->assertOk()->assertJson(['ok' => true, 'discount' => 10000]);

        // Voucher terpakai tidak boleh dihapus.
        $voucher->update(['used_count' => 1]);
        $this->delete(route('vouchers.destroy', $voucher))->assertRedirect();
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id]);

        $voucher->update(['used_count' => 0]);
        $this->delete(route('vouchers.destroy', $voucher))->assertRedirect();
        $this->assertSoftDeleted('vouchers', ['id' => $voucher->id]);
    }

    public function test_loyalty_adjust_and_gate(): void
    {
        $this->actingAsRole('custom_viewer', []);
        $customer = Customer::create(['name' => 'Pelanggan Poin', 'phone' => '0815'.random_int(10000000, 99999999)]);

        $this->get(route('loyalty.index'))->assertForbidden();
        $this->post(route('loyalty.adjust', $customer), [
            'points' => 50,
            'description' => 'Koreksi',
        ])->assertForbidden();

        $user = $this->actingAsRole('custom_cs', ['loyalty.view', 'loyalty.adjust']);
        $branchId = $user->branches()->first()->id;
        $customer->update(['branch_id' => $branchId]);

        $this->post(route('loyalty.adjust', $customer), [
            'points' => 50,
            'description' => 'Koreksi manual',
        ])->assertRedirect();
        $this->assertSame(50, (int) $customer->fresh()->loyalty_points);
        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'points' => 50,
            'type' => 'adjust',
        ]);

        $this->get(route('loyalty.index'))->assertOk();
    }

    public function test_voucher_index_requires_permission(): void
    {
        $this->actingAsRole('custom_viewer', []);

        $this->get(route('vouchers.index'))->assertForbidden();
    }
}
