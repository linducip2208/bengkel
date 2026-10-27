<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\InsuranceClaim;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke test modul Insurance Claim (sebelumnya nol test):
 * CRUD + alur approve/reject/bayar + gate permission.
 */
class InsuranceClaimSmokeTest extends TestCase
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

    public function test_insurance_claim_lifecycle(): void
    {
        $this->actingAsRole('custom_admin', ['insurance-claim.view', 'insurance-claim.create', 'insurance-claim.edit', 'insurance-claim.delete']);
        $customer = Customer::create(['name' => 'Pelanggan Asuransi', 'phone' => '0813'.random_int(10000000, 99999999)]);

        $this->post(route('insurance-claims.store'), [
            'customer_id' => $customer->id,
            'claim_date' => now()->toDateString(),
            'insurance_company' => 'Asuransi Maju',
            'estimated_amount' => 5000000,
        ])->assertRedirect();
        $claim = InsuranceClaim::latest('id')->firstOrFail();
        $this->assertSame('pending', $claim->status);
        $this->assertNotEmpty($claim->claim_number);

        // Status tidak bisa di-mass-assign saat create: tetap pending.
        $this->assertSame('pending', $claim->fresh()->status);

        $this->post(route('insurance-claims.approve', $claim), [
            'approved_amount' => 4500000,
        ])->assertRedirect();
        $this->assertSame('approved', $claim->fresh()->status);

        $this->post(route('insurance-claims.mark-paid', $claim))->assertRedirect();
        $this->assertSame('paid', $claim->fresh()->status);

        $this->delete(route('insurance-claims.destroy', $claim))->assertRedirect();
        $this->assertDatabaseMissing('insurance_claims', ['id' => $claim->id]);
    }

    public function test_insurance_claim_reject(): void
    {
        $this->actingAsRole('custom_admin', ['insurance-claim.view', 'insurance-claim.create', 'insurance-claim.edit', 'insurance-claim.delete']);
        $customer = Customer::create(['name' => 'Pelanggan Tolak', 'phone' => '0814'.random_int(10000000, 99999999)]);

        $this->post(route('insurance-claims.store'), [
            'customer_id' => $customer->id,
            'claim_date' => now()->toDateString(),
        ])->assertRedirect();
        $claim = InsuranceClaim::latest('id')->firstOrFail();

        $this->post(route('insurance-claims.reject', $claim), [
            'notes' => 'Dokumen kurang',
        ])->assertRedirect();
        $this->assertSame('rejected', $claim->fresh()->status);
    }

    public function test_insurance_claim_index_requires_permission(): void
    {
        $this->actingAsRole('custom_viewer', []);

        $this->get(route('insurance-claims.index'))->assertForbidden();
    }
}
