<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);
    }

    public function test_customer_can_approve_only_waiting_estimate_and_retry_is_idempotent(): void
    {
        $customer = Customer::create(['name' => 'Approval Customer']);
        $vehicle = $this->vehicleFor($customer);
        $service = Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'job_no' => 'BP-'.uniqid(),
            'title' => 'Pengerjaan tambahan',
            'service_date' => now(),
            'workflow_status' => 3,
            'approval_token' => str_repeat('b', 40),
        ]);

        $this->post('/approve/'.$service->approval_token)->assertRedirect();
        $this->post('/approve/'.$service->approval_token)->assertRedirect();

        $this->assertSame(4, (int) $service->fresh()->workflow_status);
        $this->assertSame(1, ActivityLog::where('event', 'estimate.approved')->count());
    }

    public function test_customer_cannot_approve_estimate_after_work_started(): void
    {
        $customer = Customer::create(['name' => 'Late Approval Customer']);
        $vehicle = $this->vehicleFor($customer);
        $service = Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'job_no' => 'BP-'.uniqid(),
            'title' => 'Terlambat approval',
            'service_date' => now(),
            'workflow_status' => 5,
            'approval_token' => str_repeat('c', 40),
        ]);

        $this->post('/approve/'.$service->approval_token)->assertStatus(409);
        $this->assertFalse((bool) $service->fresh()->is_approved);
    }

    private function vehicleFor(Customer $customer): Vehicle
    {
        $type = VehicleType::create(['vehicle_type' => 'Mobil', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Brand '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Bensin', 'slug' => uniqid()]);

        return Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B '.uniqid(),
            'model_name' => 'Model',
        ]);
    }
}
