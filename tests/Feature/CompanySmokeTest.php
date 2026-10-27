<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke test modul Company (sebelumnya nol test):
 * CRUD dasar + gate permission + branch default.
 */
class CompanySmokeTest extends TestCase
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

    public function test_company_crud_and_gate(): void
    {
        $this->actingAsRole('custom_admin', ['company.view', 'company.create', 'company.edit', 'company.delete']);

        $this->post(route('companies.store'), [
            'name' => 'PT Maju',
            'code' => 'MJU',
        ])->assertRedirect();
        $this->assertDatabaseHas('companies', ['code' => 'MJU']);

        $company = Company::where('code', 'MJU')->firstOrFail();

        $this->put(route('companies.update', $company), [
            'name' => 'PT Maju Jaya',
            'code' => 'MJU',
        ])->assertRedirect();
        $this->assertSame('PT Maju Jaya', $company->fresh()->name);

        $this->delete(route('companies.destroy', $company))->assertRedirect();
        $this->assertDatabaseMissing('companies', ['code' => 'MJU']);
    }

    public function test_company_index_requires_permission(): void
    {
        $this->actingAsRole('custom_viewer', []);

        $this->get(route('companies.index'))->assertForbidden();
    }
}
