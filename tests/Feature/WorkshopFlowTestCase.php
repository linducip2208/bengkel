<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\StockRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\ServiceService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Shared fixtures for the workshop-flow (checklist/finding/work package/QC) tests.
 */
abstract class WorkshopFlowTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);

        Role::findOrCreate('super_admin', 'web');
        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('super_admin');
        $this->actingAs($this->user);
    }

    protected function makeService(): Service
    {
        $customer = Customer::create(['name' => 'Budi Santoso', 'phone' => '0812'.str_pad((string) random_int(1000000, 9999999), 8, '0', STR_PAD_LEFT)]);
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);
        $fuel = FuelType::create(['fuel_type' => 'Bensin', 'slug' => uniqid()]);
        $category = RepairCategory::create(['repair_category_name' => 'Servis Berkala', 'slug' => uniqid(), 'is_active' => true]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'H '.strtoupper(substr(uniqid(), -6)),
            'model_name' => 'Avanza',
            'model_year' => 2021,
            'odometer' => 42000,
        ]);

        return Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'job_no' => app(ServiceService::class)->generateJobNo(),
            'title' => 'Servis berkala',
            'service_date' => now()->toDateString(),
            'workflow_status' => 2,
            'created_by' => $this->user->id,
        ]);
    }

    protected function makeProduct(string $name = 'KAMPAS REM DEPAN', float $price = 180000): Product
    {
        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => $name,
            'product_type_id' => ProductType::create(['type' => 'T'.uniqid(), 'slug' => uniqid(), 'is_active' => true])->id,
            'unit_id' => ProductUnit::create(['name' => 'N'.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true])->id,
            'price' => $price,
            'cost_price' => $price * 0.6,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 100]);

        return $product;
    }

    /**
     * @return array{0: ObservationPoint, 1: ObservationPoint, 2: ObservationPoint}
     */
    protected function makeChecklistData(): array
    {
        $engine = ObservationType::create(['observation_type' => 'MESIN']);
        $brake = ObservationType::create(['observation_type' => 'REM']);

        $oil = ObservationPoint::create(['observation_type_id' => $engine->id, 'observation_point' => 'Oli Mesin']);
        $radiator = ObservationPoint::create(['observation_type_id' => $engine->id, 'observation_point' => 'Air Radiator']);
        $pad = ObservationPoint::create(['observation_type_id' => $brake->id, 'observation_point' => 'Kampas Rem']);

        return [$oil, $radiator, $pad];
    }
}
