<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke test modul Tax Rate (sebelumnya nol test):
 * CRUD dasar + gate permission modul master-data.
 */
class TaxRateSmokeTest extends TestCase
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

    public function test_tax_rate_crud(): void
    {
        $this->actingAsRole('custom_admin', ['master-data.view', 'master-data.create', 'master-data.edit', 'master-data.delete']);

        $this->post(route('tax-rates.store'), [
            'name' => 'PPN Test',
            'rate' => 11,
            'description' => 'Pajak pertambahan nilai',
        ])->assertRedirect();
        $rate = TaxRate::where('taxname', 'PPN Test')->firstOrFail();
        $this->assertSame(11.0, (float) $rate->tax);

        $this->put(route('tax-rates.update', $rate), [
            'name' => 'PPN Test',
            'rate' => 12,
        ])->assertRedirect();
        $this->assertSame(12.0, (float) $rate->fresh()->tax);

        $this->delete(route('tax-rates.destroy', $rate))->assertRedirect();
        $this->assertSoftDeleted('tax_rates', ['id' => $rate->id]);
    }

    public function test_tax_rate_index_requires_permission(): void
    {
        $this->actingAsRole('custom_viewer', []);

        $this->get(route('tax-rates.index'))->assertForbidden();
    }
}
