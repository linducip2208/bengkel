<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\ServiceObservationPoint;
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
 * Read-only printable observation checklist.
 */
class ChecklistPrintTest extends TestCase
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

    private function makeService(): Service
    {
        $customer = Customer::create(['name' => 'Budi Santoso', 'phone' => '081234567890']);
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);
        $fuel = FuelType::create(['fuel_type' => 'Bensin', 'slug' => uniqid()]);
        $category = RepairCategory::create(['repair_category_name' => 'Servis Berkala', 'slug' => uniqid(), 'is_active' => true]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'H 1234 AB',
            'model_name' => 'Avanza',
            'model_year' => 2021,
            'odometer' => 42000,
        ]);

        return Service::create(['customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'job_no' => app(ServiceService::class)->generateJobNo(),
            'title' => 'Servis berkala',
            'service_date' => now()->toDateString(),
            'workflow_status' => 2,
            'created_by' => $this->user->id,
        ]);
    }

    private function makeChecklistData(): array
    {
        $engine = ObservationType::create(['observation_type' => 'MESIN']);
        $brake = ObservationType::create(['observation_type' => 'REM']);

        $oil = ObservationPoint::create(['observation_type_id' => $engine->id, 'observation_point' => 'Oli Mesin']);
        $radiator = ObservationPoint::create(['observation_type_id' => $engine->id, 'observation_point' => 'Air Radiator']);
        $pad = ObservationPoint::create(['observation_type_id' => $brake->id, 'observation_point' => 'Kampas Rem']);

        return [$oil, $radiator, $pad];
    }

    public function test_print_route_loads_for_authorized_user(): void
    {
        $service = $this->makeService();

        $this->get(route('observations.checklist.print', $service))
            ->assertOk()
            ->assertSee('CHECKLIST PEMERIKSAAN KENDARAAN')
            ->assertSee($service->job_no);
    }

    public function test_print_requires_authentication(): void
    {
        $service = $this->makeService();
        $this->app->make('auth')->guard()->logout();

        $this->get(route('observations.checklist.print', $service))->assertRedirect(route('login'));
    }

    public function test_printed_checklist_uses_saved_observation_values(): void
    {
        [$oil, $radiator, $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        // Save one checked point with a comment, leave others untouched.
        ServiceObservationPoint::create([
            'service_id' => $service->id,
            'observation_point_id' => $oil->id,
            'checked' => true,
            'comment' => 'Kurang 0.5L',
        ]);

        $response = $this->get(route('observations.checklist.print', $service));

        $response->assertOk()
            ->assertSee('Oli Mesin')
            ->assertSee('Air Radiator')
            ->assertSee('Kampas Rem')
            ->assertSee('Kurang 0.5L')
            // Un-inspected points render plain un-checked values.
            ->assertSee('Belum Diperiksa')
            // Plain document values, no editable form controls.
            ->assertDontSee('<input', false)
            ->assertDontSee('<checkbox', false);
    }

    public function test_print_renders_grouped_sections_and_vehicle_data(): void
    {
        $this->makeChecklistData();
        $service = $this->makeService();

        $response = $this->get(route('observations.checklist.print', $service));

        $response->assertOk()
            ->assertSee('MESIN')
            ->assertSee('REM')
            ->assertSee('Budi Santoso')
            // Customer phone is normalized on save (0… → +62…).
            ->assertSee('+6281234567890')
            ->assertSee('H 1234 AB')
            ->assertSee('Toyota')
            ->assertSee('Avanza')
            ->assertSee('42.000');
    }

    public function test_printing_does_not_modify_database(): void
    {
        [$oil, $radiator, $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        ServiceObservationPoint::create([
            'service_id' => $service->id,
            'observation_point_id' => $oil->id,
            'checked' => true,
            'comment' => 'aman',
        ]);

        $resultsBefore = ServiceObservationPoint::where('service_id', $service->id)->get(['observation_point_id', 'checked', 'comment'])->toArray();
        $workflowBefore = $service->workflow_status;

        $this->get(route('observations.checklist.print', $service))->assertOk();
        $this->get(route('observations.checklist.print', $service))->assertOk();

        $service->refresh();
        $this->assertSame($workflowBefore, $service->workflow_status);
        $this->assertSame(
            $resultsBefore,
            ServiceObservationPoint::where('service_id', $service->id)->get(['observation_point_id', 'checked', 'comment'])->toArray(),
            'Printing must be read-only.'
        );
        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseCount('invoices', 0);
    }
}
