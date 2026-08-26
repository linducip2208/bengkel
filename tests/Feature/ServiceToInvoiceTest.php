<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Invoice;
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
use App\Services\InvoiceService;
use App\Services\ServiceService;
use App\Services\StockService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Service/job-card state machine: completion is idempotent and invalid
 * transitions are rejected.
 */
class ServiceToInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);

        // Services index resolves technicians via spatie roles.
        Role::findOrCreate('mekanik', 'web');

        $this->actingAs($this->makeUser());
    }

    private function makeUser(string $role = 'super_admin'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function makeService(): Service
    {
        $customer = Customer::create(['name' => 'Cust SVC '.uniqid()]);
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Pertamax', 'slug' => uniqid()]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B '.uniqid(),
            'model_name' => 'Avanza',
            'odometer' => 10000,
        ]);

        $category = RepairCategory::create([
            'repair_category_name' => 'Tune Up '.uniqid(),
            'slug' => uniqid(),
            'is_active' => true,
        ]);

        return Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'job_no' => app(ServiceService::class)->generateJobNo(),
            'title' => 'Servis Uji',
            'description' => 'Keluhan uji',
            'charge' => 50000,
            'service_date' => now()->toDateString(),
            'done_status' => 0,
            'workflow_status' => 0,
            'created_by' => auth()->id() ?? $this->makeUser()->id,
        ]);
    }

    public function test_double_completion_creates_only_one_invoice(): void
    {
        $service = $this->makeService();

        $first = $this->post("/services/{$service->id}/complete");
        $first->assertRedirect();

        $second = $this->post("/services/{$service->id}/complete");
        $second->assertRedirect();
        $second->assertSessionHas('info');

        $invoices = Invoice::withoutGlobalScopes()
            ->where('service_id', $service->id)
            ->get();

        $this->assertCount(1, $invoices);
        $this->assertEquals(12, $service->fresh()->workflow_status);
        $this->assertEquals(2, $service->fresh()->done_status);
    }

    public function test_cannot_advance_beyond_completed(): void
    {
        $service = $this->makeService();
        $service->update(['workflow_status' => 12, 'completed_at' => now()]);

        $response = $this->get('/services');
        $response->assertOk();

        $result = $this->post("/services/{$service->id}/advance");
        $result->assertSessionHas('error');

        $this->assertEquals(12, $service->fresh()->workflow_status);
    }

    public function test_cannot_start_a_completed_service(): void
    {
        $service = $this->makeService();
        $service->update(['workflow_status' => 12, 'done_status' => 2]);

        $result = $this->post("/services/{$service->id}/start");
        $result->assertSessionHas('error');

        $this->assertNull($service->fresh()->started_at);
    }

    public function test_invoice_derives_totals_from_charge_and_parts(): void
    {
        // Part consumed during the service (2 × 25.000 = 50.000 parts revenue)
        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Filter Oli',
            'product_type_id' => ProductType::create(['type' => 'T'.uniqid(), 'slug' => uniqid(), 'is_active' => true])->id,
            'unit_id' => ProductUnit::create(['name' => 'N'.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true])->id,
            'price' => 25000,
            'cost_price' => 15000,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 5]);

        $service = $this->makeService();
        app(StockService::class)->decrement(
            $product->id, 2, 'usage', 'Digunakan dalam servis',
            Service::class, $service->id,
        );

        $invoice = app(InvoiceService::class)->generateInvoiceNumber();
        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{4}$/', $invoice);

        $result = $this->post("/services/{$service->id}/complete");
        $result->assertRedirect();

        $created = Invoice::withoutGlobalScopes()->where('service_id', $service->id)->first();

        $this->assertNotNull($created);
        // charge 50.000 + parts 2×25.000 = 100.000
        $this->assertEquals(100000.0, (float) $created->grand_total);
        $this->assertCount(2, $created->items()->withoutGlobalScopes()->get());
    }
}
