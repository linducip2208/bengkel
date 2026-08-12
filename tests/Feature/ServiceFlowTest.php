<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\LoyaltyTransaction;
use App\Models\PaymentMethod;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\ServiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\RequirePair::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        ]);
    }

    private function makeUser(string $role = 'super_admin'): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Budi Santoso',
        ]);
    }

    private function makeVehicle(Customer $customer): Vehicle
    {
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => 'mpv']);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);
        $fuel = FuelType::create(['fuel_type' => 'Pertamax', 'slug' => 'pertamax']);

        return Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B 1234 ABC',
            'model_name' => 'Avanza',
            'model_year' => 2020,
            'odometer' => 50000,
        ]);
    }

    private function makeRepairCategory(): RepairCategory
    {
        return RepairCategory::create([
            'repair_category_name' => 'Tune Up',
            'slug' => 'tune-up',
            'is_active' => true,
        ]);
    }

    private function makeService(Customer $customer, Vehicle $vehicle, RepairCategory $category, User $technician): Service
    {
        $jobNo = app(ServiceService::class)->generateJobNo();

        $service = Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'title' => 'Tune Up Rutin 50.000 km',
            'description' => 'Servis berkala',
            'service_date' => now(),
            'charge' => 250000,
            'done_status' => 0,
            'workflow_status' => 0,
            'job_no' => $jobNo,
            'created_by' => auth()->id(),
            'assign_to' => $technician->id,
        ]);

        $service->technicians()->attach($technician->id);

        return $service;
    }

    public function test_service_is_persisted_with_job_no_and_technician(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle($customer);
        $category = $this->makeRepairCategory();
        $technician = $this->makeUser('mekanik');

        $service = $this->makeService($customer, $vehicle, $category, $technician);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'job_no' => $service->job_no,
        ]);

        $this->assertMatchesRegularExpression('/^BP-\d{8}-\d{3}$/', $service->job_no);
        $this->assertEquals(0, $service->workflow_status);

        $this->assertDatabaseHas('service_technicians', [
            'service_id' => $service->id,
            'user_id' => $technician->id,
        ]);
    }

    public function test_advance_workflow_sets_checked_in_at(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle($customer);
        $category = $this->makeRepairCategory();
        $technician = $this->makeUser('mekanik');
        $service = $this->makeService($customer, $vehicle, $category, $technician);

        $this->assertNull($service->checked_in_at);

        $response = $this->post("/services/{$service->id}/advance");

        $response->assertRedirect();

        $service->refresh();
        $this->assertEquals(1, $service->workflow_status);
        $this->assertNotNull($service->checked_in_at);
    }

    public function test_complete_service_creates_linked_invoice(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle($customer);
        $category = $this->makeRepairCategory();
        $technician = $this->makeUser('mekanik');
        $service = $this->makeService($customer, $vehicle, $category, $technician);

        $response = $this->post("/services/{$service->id}/complete");

        $response->assertRedirect();

        $service->refresh();
        $this->assertEquals(12, $service->workflow_status);
        $this->assertEquals(2, $service->done_status);
        $this->assertNotNull($service->completed_at);

        $invoice = Invoice::where('service_id', $service->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($service->customer_id, $invoice->customer_id);
        $this->assertEquals(0, (int) $invoice->payment_status);
        $this->assertEquals('service', $invoice->invoice_type);
    }

    public function test_invoice_payment_marks_paid_creates_income_and_loyalty_points(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle($customer);
        $category = $this->makeRepairCategory();
        $technician = $this->makeUser('mekanik');
        $service = $this->makeService($customer, $vehicle, $category, $technician);

        $this->post("/services/{$service->id}/complete");

        $invoice = Invoice::where('service_id', $service->id)->firstOrFail();

        $paymentMethod = PaymentMethod::create([
            'payment' => 'Cash',
            'slug' => 'cash',
            'is_active' => true,
        ]);

        // Default chart of accounts referenced by the auto-journal on payment.
        ChartOfAccount::create(['code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        ChartOfAccount::create(['code' => '1010', 'name' => 'Bank', 'type' => 'asset', 'is_active' => true]);
        ChartOfAccount::create(['code' => '4000', 'name' => 'Service Revenue', 'type' => 'income', 'is_active' => true]);

        $response = $this->post("/invoices/{$invoice->id}/payments", [
            'amount' => $invoice->grand_total,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(2, (int) $invoice->payment_status);

        $this->assertDatabaseHas('incomes', [
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $customer->id,
        ]);

        $income = Income::where('invoice_number', $invoice->invoice_number)->first();
        $this->assertEquals((float) $invoice->grand_total, (float) $income->amount);

        $expectedPoints = (int) floor($invoice->grand_total / 1000);
        $this->assertGreaterThan(0, $expectedPoints);

        $customer->refresh();
        $this->assertEquals($expectedPoints, $customer->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'points' => $expectedPoints,
            'type' => 'earn',
        ]);
    }
}
