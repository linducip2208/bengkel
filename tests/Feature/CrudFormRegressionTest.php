<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regresi untuk field form yang diam-diam terbuang dan modal tanpa
 * feedback: kota/kode-pos customer, deskripsi kendaraan, kunci error
 * yang salah, dan modal tambah user yang tidak terbuka ulang.
 */
class CrudFormRegressionTest extends TestCase
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
        if (! in_array($role, ['super_admin', 'admin'], true)) {
            $branch = Branch::create(['name' => 'Cabang Uji '.uniqid(), 'code' => 'T'.uniqid(), 'is_active' => true]);
            $user->branches()->sync([$branch->id]);
        }
        $this->actingAs($user);

        return $user;
    }

    public function test_customer_city_and_postal_code_are_persisted(): void
    {
        $this->actingAsRole('custom_cs', ['customer.create', 'customer.view']);

        $this->post(route('customers.store'), [
            'name' => 'Pelanggan Kota',
            'phone' => '0812000001',
            'address' => 'Jl. Merdeka 1',
            'city' => 'Semarang',
            'postal_code' => '50123',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'phone' => '+62812000001',
            'city' => 'Semarang',
            'postal_code' => '50123',
        ]);
    }

    public function test_vehicle_description_is_persisted(): void
    {
        $this->actingAsRole('custom_kendaraan', ['vehicle.create', 'vehicle.view']);
        $customer = Customer::create(['name' => 'Pemilik', 'phone' => '0812000002']);

        $this->post(route('vehicles.store'), [
            'customer_id' => $customer->id,
            'number_plate' => 'H 1234 XYZ',
            'description' => 'Lecet pintu kiri',
        ])->assertRedirect();

        $this->assertDatabaseHas('vehicles', [
            'number_plate' => 'H 1234 XYZ',
            'description' => 'Lecet pintu kiri',
        ]);
    }

    public function test_vehicle_duplicate_plate_reports_number_plate_key(): void
    {
        $this->actingAsRole('custom_kendaraan2', ['vehicle.create', 'vehicle.view']);
        $customer = Customer::create(['name' => 'Pemilik 2', 'phone' => '0812000003']);
        $customer->vehicles()->create(['number_plate' => 'H 9999 ZZ']);

        $this->post(route('vehicles.store'), [
            'customer_id' => $customer->id,
            'number_plate' => 'H 9999 ZZ',
        ])->assertSessionHasErrors('number_plate');
    }

    public function test_user_store_failure_keeps_input_for_add_modal(): void
    {
        Role::findOrCreate('kasir', 'web');
        $this->actingAsRole('custom_hrd', ['user.create', 'user.edit']);
        User::factory()->create(['email' => 'sudah.ada@bengkel.test']);

        $response = $this->post(route('users.store'), [
            '_form' => 'add-user',
            'name' => 'Calon User',
            'email' => 'sudah.ada@bengkel.test',
            'password' => 'secret123',
            'role' => 'kasir',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasInput('_form', 'add-user');
        $response->assertSessionHasInput('name', 'Calon User');
    }

    public function test_user_store_ignores_privileged_fields(): void
    {
        Role::findOrCreate('kasir', 'web');
        $this->actingAsRole('custom_hrd2', ['user.create', 'user.edit']);

        $this->post(route('users.store'), [
            'name' => 'User Biasa',
            'email' => 'biasa@bengkel.test',
            'password' => 'secret123',
            'role' => 'kasir',
            'base_salary' => 99999999,
            'email_verified_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $user = User::where('email', 'biasa@bengkel.test')->firstOrFail();
        $this->assertNotSame(99999999.0, (float) $user->base_salary);
        $this->assertNull($user->email_verified_at);
    }
}
