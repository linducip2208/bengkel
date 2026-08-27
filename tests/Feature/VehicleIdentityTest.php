<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);
        $this->actingAs(User::factory()->create(['is_active' => true]));
    }

    public function test_vehicle_identifiers_normalize_before_uniqueness_validation(): void
    {
        $customer = Customer::create(['name' => 'Andi', 'phone' => '081111111111']);
        $identity = $this->vehicleReferences();
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            ...$identity,
            'number_plate' => ' b   1234  abc ',
            'chassis_number' => ' mhk-001 ',
            'engine_number' => ' eng-001 ',
        ]);

        $this->assertSame('B 1234 ABC', $vehicle->number_plate);
        $this->assertSame('MHK-001', $vehicle->chassis_number);
        $this->assertSame('ENG-001', $vehicle->engine_number);

        $this->post(route('vehicles.store'), [
            'customer_id' => $customer->id,
            ...$identity,
            'number_plate' => 'B  1234 ABC',
        ])->assertSessionHasErrors('number_plate');
        $this->assertSame(1, Vehicle::withoutGlobalScopes()->count());
    }

    public function test_update_retains_own_identifiers_but_cannot_take_another_vehicle_identity(): void
    {
        $customer = Customer::create(['name' => 'Andi', 'phone' => '081111111112']);
        $identity = $this->vehicleReferences();
        $first = Vehicle::create(['customer_id' => $customer->id, ...$identity, 'number_plate' => 'B 1 AA', 'chassis_number' => 'CH-1']);
        $second = Vehicle::create(['customer_id' => $customer->id, ...$identity, 'number_plate' => 'B 2 BB', 'chassis_number' => 'CH-2']);
        $before = Vehicle::withoutGlobalScopes()->count();

        $this->put(route('vehicles.update', $first), [
            'customer_id' => $customer->id,
            ...$identity,
            'number_plate' => ' b 1 aa ',
            'chassis_number' => ' ch-1 ',
        ])->assertSessionHasNoErrors();
        $this->assertSame($first->id, $first->fresh()->id);
        $this->assertSame($before, Vehicle::withoutGlobalScopes()->count());

        $this->put(route('vehicles.update', $first), [
            'customer_id' => $customer->id,
            ...$identity,
            'number_plate' => $second->number_plate,
        ])->assertSessionHasErrors('number_plate');
    }

    private function vehicleReferences(): array
    {
        $type = VehicleType::createWithUniqueSlug(['vehicle_type' => 'Mobil']);
        $brand = VehicleBrand::create([
            'vehicle_type_id' => $type->id,
            'vehicle_brand' => 'Toyota',
        ]);
        $fuel = FuelType::createWithUniqueSlug(['fuel_type' => 'Bensin']);

        return [
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
        ];
    }
}
