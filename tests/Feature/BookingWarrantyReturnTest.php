<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SellReturn;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\BookingService;
use App\Services\WarrantyClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingWarrantyReturnTest extends TestCase
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

        return $user;
    }

    private function makeVehicle(Customer $customer): Vehicle
    {
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Pertalite', 'slug' => uniqid()]);

        return Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B '.uniqid().' XX',
            'model_name' => 'Avanza',
        ]);
    }

    // ------------------------------------------------------------ Booking

    public function test_booking_conversion_is_idempotent_and_carries_technician(): void
    {
        $this->authed();
        $technician = $this->authed('mekanik');
        $customer = Customer::create(['name' => 'B Cust', 'phone' => '0813333333']);
        $vehicle = $this->makeVehicle($customer);

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'name' => 'B Cust',
            'phone' => '0813333333',
            'vehicle_plate' => $vehicle->number_plate,
            'complaint' => 'Oli bocor',
            'booking_at' => now(),
            'status' => 'pending',
            'technician_id' => $technician->id,
        ]);

        $service = app(BookingService::class)->convertToService($booking);

        $this->assertNotNull($service);
        $this->assertEquals('confirmed', $booking->fresh()->status);
        $this->assertEquals($service->id, $booking->fresh()->service_id);

        // Technician carried onto the service.
        $this->assertContains($technician->id, $service->fresh()->technicians->pluck('id')->all());
        $this->assertEquals($technician->id, $service->fresh()->assign_to);

        // Second conversion reuses the same service (idempotency).
        $again = app(BookingService::class)->convertToService($booking->fresh());
        $this->assertEquals($service->id, $again->id);
        $this->assertSame(1, Service::count());
    }

    // ------------------------------------------------------------ Warranty

    public function test_warranty_claim_rejects_invalid_transition(): void
    {
        $this->authed();
        $customer = Customer::create(['name' => 'W Cust', 'phone' => '0814444444']);
        $product = $this->makeProduct();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-W-'.uniqid(),
            'customer_id' => $customer->id,
            'payment_status' => 2,
            'total_amount' => 50000,
            'grand_total' => 50000,
            'invoice_type' => 'service',
        ]);
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'description' => 'Part',
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
            'warranty_expiry' => now()->addMonths(6)->toDateString(),
        ]);

        $claim = app(WarrantyClaimService::class)->create([
            'invoice_item_id' => $item->id,
            'claim_date' => now()->toDateString(),
            'complaint' => 'Rusak',
        ]);
        $this->assertEquals('submitted', $claim->status);

        $claim->refresh();
        $this->assertEquals('submitted', $claim->status);

        // Valid: submitted -> approved
        $approved = app(WarrantyClaimService::class)->transition($claim, 'approved');
        $this->assertEquals('approved', $approved->status);

        // Invalid: approved -> submitted (would regress).
        $this->expectException(\RuntimeException::class);
        app(WarrantyClaimService::class)->transition($approved->refresh(), 'submitted');
    }

    public function test_warranty_claim_rejects_expired_warranty(): void
    {
        $this->authed();
        $customer = Customer::create(['name' => 'W2 Cust', 'phone' => '0815555555']);
        $product = $this->makeProduct();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-W2-'.uniqid(),
            'customer_id' => $customer->id,
            'payment_status' => 2,
            'total_amount' => 50000,
            'grand_total' => 50000,
            'invoice_type' => 'service',
        ]);
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'description' => 'Part',
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
            'warranty_expiry' => now()->subDays(1)->toDateString(),
        ]);

        $this->expectException(\RuntimeException::class);
        app(WarrantyClaimService::class)->create([
            'invoice_item_id' => $item->id,
            'claim_date' => now()->toDateString(),
            'complaint' => 'Rusak',
        ]);
    }

    // ------------------------------------------------------------ Sell return

    public function test_sell_return_rejects_quantity_exceeding_original_sale(): void
    {
        $user = $this->authed();
        $customer = Customer::create(['name' => 'R Cust', 'phone' => '0816666666']);
        $product = $this->makeProduct();
        $vehicle = $this->makeVehicle($customer);
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'sale_date' => now()->toDateString(),
            'sales_no' => 'SL-'.uniqid(),
            'total_amount' => 100000,
            'grand_total' => 100000,
            'created_by' => $user->id,
            'status' => 'completed',
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50000,
            'total_price' => 100000,
        ]);

        $this->actingAs($user)->post(route('sell-returns.store'), [
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Barang cacat',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 50000],
            ],
        ]);

        $this->assertSame(0, SellReturn::count(), 'Over-return must be rejected.');
    }

    public function test_sell_return_allows_exact_remaining_quantity(): void
    {
        $user = $this->authed();
        $customer = Customer::create(['name' => 'R2 Cust', 'phone' => '0817777777']);
        $product = $this->makeProduct();
        $vehicle = $this->makeVehicle($customer);
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'sale_date' => now()->toDateString(),
            'sales_no' => 'SL-'.uniqid(),
            'total_amount' => 100000,
            'grand_total' => 100000,
            'created_by' => $user->id,
            'status' => 'completed',
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50000,
            'total_price' => 100000,
        ]);

        $response = $this->actingAs($user)->post(route('sell-returns.store'), [
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Barang cacat',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 50000],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, SellReturn::count());
        $sellReturn = SellReturn::first();
        $this->assertEquals(100000, (float) $sellReturn->refund_amount);
    }

    private function makeProduct(): Product
    {
        $type = ProductType::create(['type' => 'Sparepart', 'slug' => uniqid(), 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs', 'is_active' => true]);

        return Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Part '.uniqid(),
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 50000,
            'warranty' => '12 bulan',
        ]);
    }
}
