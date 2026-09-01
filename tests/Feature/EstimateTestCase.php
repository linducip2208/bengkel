<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\StockRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\EstimateService;
use App\Services\ServiceService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Shared fixtures for the estimate domain tests.
 */
abstract class EstimateTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);

        $this->user = $this->makeUser('super_admin');
        $this->actingAs($this->user);
    }

    protected function makeUser(string $role = 'super_admin'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    protected function grantPermission(User $user, string $permission): User
    {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);

        return $user;
    }

    protected function makeService(array $overrides = []): Service
    {
        $customer = Customer::create(['name' => 'Cust '.uniqid()]);
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Bensin', 'slug' => uniqid()]);
        $category = RepairCategory::create([
            'repair_category_name' => 'Servis '.uniqid(),
            'slug' => uniqid(),
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B '.uniqid(),
            'model_name' => 'Avanza',
            'model_year' => 2020,
            'odometer' => 25000,
        ]);

        return Service::create(array_merge([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'job_no' => app(ServiceService::class)->generateJobNo(),
            'title' => 'Oli + Tune Up',
            'description' => 'Keluhan uji',
            'charge' => 50000,
            'service_date' => now()->toDateString(),
            'workflow_status' => 2,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    protected function makeProduct(string $name = 'FILTER OLI', float $price = 120000): Product
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
     * Mixed part + labor item payload (free-text labor, no product_id).
     */
    protected function itemPayload(array $overrides = []): array
    {
        return array_merge([
            'item_type' => 'labor',
            'product_id' => null,
            'description' => 'JASA O/H KOPLING',
            'quantity' => 1,
            'unit_price' => 350000,
            'discount' => 0,
            'discount_type' => 'fixed',
            'tax_rate' => null,
        ], $overrides);
    }

    protected function partPayload(Product $product, array $overrides = []): array
    {
        return $this->itemPayload(array_merge([
            'item_type' => 'part',
            'product_id' => $product->id,
            'description' => $product->name,
            'unit_price' => (float) $product->price,
        ], $overrides));
    }

    protected function storePayload(array $items, array $header = []): array
    {
        return array_merge([
            'estimate_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'discount' => 0,
            'discount_type' => 'fixed',
            'notes' => 'Keluhan pemeriksaan',
            'items' => $items,
        ], $header);
    }

    protected function issueEstimate(Service $service, ?array $items = null): ServiceEstimate
    {
        $service->update(['workflow_status' => 2]);

        $estimate = app(EstimateService::class)->createDraft(
            $service,
            ['valid_until' => now()->addDays(7)->toDateString()],
            $items ?? [$this->itemPayload()],
        );

        return app(EstimateService::class)->markSent($estimate, 'test');
    }
}
