<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FleetContract;
use App\Models\FuelType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke test modul Fleet Contract (sebelumnya nol test):
 * CRUD dasar + gate permission.
 */
class FleetContractSmokeTest extends TestCase
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

    private function makeCustomer(): Customer
    {
        return Customer::create(['name' => 'Pelanggan Fleet', 'phone' => '0812'.random_int(10000000, 99999999)]);
    }

    private function makeVehicle(Customer $customer): Vehicle
    {
        $vtype = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => uniqid()]);
        $vbrand = VehicleBrand::create(['vehicle_type_id' => $vtype->id, 'vehicle_brand' => 'Toyota '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Pertalite', 'slug' => uniqid()]);

        return Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $vtype->id,
            'vehicle_brand_id' => $vbrand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B '.uniqid(),
            'model_name' => 'Avanza',
        ]);
    }

    public function test_fleet_contract_crud(): void
    {
        $this->actingAsRole('custom_admin', ['fleet-contract.view', 'fleet-contract.create', 'fleet-contract.edit', 'fleet-contract.delete']);
        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle($customer);

        $this->post(route('fleet-contracts.store'), [
            'customer_id' => $customer->id,
            'name' => 'Kontrak Fleet A',
            'service_interval_days' => 90,
        ])->assertRedirect();
        $contract = FleetContract::where('name', 'Kontrak Fleet A')->firstOrFail();

        $this->put(route('fleet-contracts.update', $contract), [
            'customer_id' => $customer->id,
            'name' => 'Kontrak Fleet A Revisi',
            'service_interval_days' => 60,
            'vehicle_ids' => [$vehicle->id],
        ])->assertRedirect();
        $this->assertSame('Kontrak Fleet A Revisi', $contract->fresh()->name);
        $this->assertCount(1, $contract->fresh()->vehicles);

        $this->get(route('fleet-contracts.show', $contract))->assertOk();

        $this->delete(route('fleet-contracts.destroy', $contract))->assertRedirect();
        $this->assertDatabaseMissing('fleet_contracts', ['id' => $contract->id]);
    }

    public function test_fleet_contract_index_requires_permission(): void
    {
        $this->actingAsRole('custom_viewer', []);

        $this->get(route('fleet-contracts.index'))->assertForbidden();
    }
}
