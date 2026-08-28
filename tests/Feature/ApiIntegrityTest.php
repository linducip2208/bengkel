<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\RepairCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\StockRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\ServiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * API integrity: financial fields cannot be spoofed, resource writes are
 * role-gated, and system-generated documents cannot be manually edited.
 */
class ApiIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class]);
    }

    private function authed(string $role = 'admin'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    private function makeCustomer(): Customer
    {
        return Customer::create(['name' => 'API Cust '.uniqid(), 'phone' => '081'.mt_rand(1000000, 9999999)]);
    }

    private function makeRepairCategory(): RepairCategory
    {
        return RepairCategory::create(['repair_category_name' => 'General '.uniqid(), 'slug' => uniqid(), 'is_active' => true]);
    }

    private function makeService(): Service
    {
        $customer = $this->makeCustomer();
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
        ]);

        return Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $this->makeRepairCategory()->id,
            'job_no' => app(ServiceService::class)->generateJobNo(),
            'title' => 'Servis API',
            'charge' => 50000,
            'service_date' => now()->toDateString(),
            'done_status' => 0,
            'workflow_status' => 0,
            'created_by' => auth()->id(),
        ]);
    }

    private function makeVehicle(Customer $customer): Vehicle
    {
        $type = VehicleType::create(['vehicle_type' => 'Hatchback', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Honda '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Pertamax', 'slug' => uniqid()]);

        return Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'D '.uniqid(),
            'model_name' => 'Brio',
        ]);
    }

    private function makePaymentMethod(): PaymentMethod
    {
        return PaymentMethod::create(['payment' => 'Cash'.uniqid(), 'slug' => uniqid(), 'is_active' => true]);
    }

    private function makeProduct(int $price = 50000, int $stock = 10): Product
    {
        $type = ProductType::create(['type' => 'Sparepart', 'slug' => uniqid(), 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs', 'is_active' => true]);
        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Part '.uniqid(),
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => $price,
            'cost_price' => $price * 0.7,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => $stock]);

        return $product;
    }

    public function test_api_invoice_store_cannot_spoof_paid_status(): void
    {
        $this->authed('admin');
        $service = $this->makeService();

        $response = $this->postJson('/api/v1/invoices', [
            'service_id' => $service->id,
            'invoice_date' => now()->toDateString(),
            'discount' => 0,
            'notes' => 'coba',
            'payment_status' => 2,
            'paid_amount' => 50000,
        ]);

        $response->assertCreated();
        $invoice = Invoice::withoutGlobalScopes()->where('service_id', $service->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(0, (int) $invoice->payment_status);
        $this->assertSame(0.0, (float) $invoice->paid_amount);
    }

    public function test_api_invoice_update_rejects_payment_status_spoof(): void
    {
        $this->authed('admin');
        $service = $this->makeService();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-'.uniqid(),
            'customer_id' => $service->customer_id,
            'service_id' => $service->id,
            'payment_status' => 0,
            'total_amount' => 50000,
            'grand_total' => 50000,
            'paid_amount' => 0,
            'amount_received' => 0,
        ]);

        $this->putJson("/api/v1/invoices/{$invoice->id}", ['payment_status' => 2, 'notes' => 'x'])
            ->assertStatus(422);

        $this->assertSame(0, (int) $invoice->fresh()->payment_status);
    }

    public function test_api_sale_store_decrements_stock_and_creates_items(): void
    {
        $this->authed('admin');
        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle($customer);
        $product = $this->makeProduct(50000, 10);
        $this->assertEquals(10, StockRecord::where('product_id', $product->id)->first()->quantity);

        $response = $this->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 50000]],
        ]);

        $response->assertCreated();
        $sale = Sale::withoutGlobalScopes()->orderByDesc('id')->first();
        $this->assertNotNull($sale);
        $lineItem = SaleItem::where('sale_id', $sale->id)->firstOrFail();
        $this->assertSame(2.0, (float) $lineItem->quantity);
        $this->assertSame(8.0, (float) StockRecord::where('product_id', $product->id)->first()->quantity);
        $this->assertEquals(100000, (float) $sale->grand_total);
    }

    public function test_api_sale_cannot_update_or_delete_when_invoiced(): void
    {
        $this->authed('admin');
        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle($customer);
        $product = $this->makeProduct(50000, 10);
        $sale = Sale::create([
            'sales_no' => 'SLS-'.uniqid(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 50000,
            'grand_total' => 50000,
            'created_by' => auth()->id(),
        ]);
        Invoice::create([
            'invoice_number' => 'INV-'.uniqid(),
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'payment_status' => 0,
            'total_amount' => 50000,
            'grand_total' => 50000,
            'paid_amount' => 0,
            'amount_received' => 0,
        ]);

        $this->putJson("/api/v1/sales/{$sale->id}", [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50000]],
        ])->assertStatus(422);

        $this->deleteJson("/api/v1/sales/{$sale->id}")->assertStatus(422);

        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    public function test_api_system_generated_income_cannot_be_edited_or_deleted(): void
    {
        $this->authed('admin');
        $customer = $this->makeCustomer();
        $income = Income::create([
            'invoice_number' => 'INV-'.uniqid(),
            'customer_id' => $customer->id,
            'payment_method_id' => $this->makePaymentMethod()->id,
            'amount' => 100000,
            'income_date' => now()->toDateString(),
            'label' => 'POS auto',
            'created_by' => auth()->id(),
        ]);

        $this->deleteJson("/api/v1/incomes/{$income->id}")->assertStatus(422);
        $this->putJson("/api/v1/incomes/{$income->id}", ['amount' => 50000])->assertStatus(422);

        $this->assertEquals(100000, (float) $income->fresh()->amount);
    }

    public function test_api_service_complete_is_idempotent(): void
    {
        $this->authed('service_advisor');
        $service = $this->makeService();

        $first = $this->postJson("/api/v1/services/{$service->id}/complete");
        $first->assertOk();

        $second = $this->postJson("/api/v1/services/{$service->id}/complete");
        $second->assertOk();
        $this->assertTrue($second->json('already_processed'));

        $this->assertSame(1, Invoice::where('service_id', $service->id)->count());
    }
}
