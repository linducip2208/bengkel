<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventRequestForgery;
use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FuelType;
use App\Models\Income;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Menjaga output agregat laporan tetap benar setelah optimasi query
 * (GROUP BY tunggal vs loop per cabang/per bulan).
 */
class ReportsAggregationTest extends TestCase
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

    public function test_branch_comparison_aggregates_per_branch(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($user);

        $branchA = Branch::create(['name' => 'A '.uniqid(), 'code' => 'A'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'B '.uniqid(), 'code' => 'B'.uniqid(), 'is_active' => true]);

        $customer = Customer::create(['name' => 'C', 'phone' => '0811', 'branch_id' => $branchA->id]);
        $vehicle = $this->makeVehicle($customer);
        Service::create([
            'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'branch_id' => $branchA->id,
            'title' => 'S1', 'service_date' => now()->subDays(2)->toDateString(), 'charge' => 100000,
            'created_by' => $user->id,
        ]);
        Service::create([
            'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'branch_id' => $branchA->id,
            'title' => 'S2', 'service_date' => now()->subDays(2)->toDateString(), 'charge' => 50000,
            'created_by' => $user->id,
        ]);
        Service::create([
            'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'branch_id' => $branchB->id,
            'title' => 'S3', 'service_date' => now()->subDays(2)->toDateString(), 'charge' => 25000,
            'created_by' => $user->id,
        ]);

        $report = app(ReportService::class)->branchComparison([
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $byId = $report['branches']->keyBy('id');
        $this->assertSame(150000.0, (float) $byId[$branchA->id]['service_revenue']);
        $this->assertSame(2, $byId[$branchA->id]['service_count']);
        $this->assertSame(25000.0, (float) $byId[$branchB->id]['service_revenue']);
        $this->assertSame(175000.0, (float) $report['total_revenue']);
    }

    public function test_financial_monthly_breakdown_matches_totals(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($user);

        Income::create([
            'invoice_number' => 'INC-'.uniqid(), 'amount' => 1000000,
            'income_date' => now()->startOfMonth()->toDateString(), 'label' => 'Jasa',
            'created_by' => $user->id,
        ]);
        Expense::create([
            'expense_date' => now()->startOfMonth()->toDateString(), 'amount' => 400000,
            'label' => 'Operasional', 'created_by' => $user->id,
        ]);

        $report = app(ReportService::class)->financialReport([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);

        $this->assertSame(1000000.0, (float) $report['total_income']);
        $this->assertSame(400000.0, (float) $report['total_expense']);

        $months = collect($report['monthly_breakdown']);
        $this->assertSame(1000000.0, (float) $months->sum('income'));
        $this->assertSame(400000.0, (float) $months->sum('expense'));
        $this->assertSame(600000.0, (float) $months->sum('profit'));
    }

    public function test_service_report_technician_names_resolved(): void
    {
        $tech = User::factory()->create(['is_active' => true, 'name' => 'Teknisi Uji']);
        $branch = Branch::create(['name' => 'Cabang T '.uniqid(), 'code' => 'T'.uniqid(), 'is_active' => true]);
        $tech->branches()->sync([$branch->id]);
        $this->actingAs($tech);
        $customer = Customer::create(['name' => 'C2', 'phone' => '0812']);
        Service::create([
            'customer_id' => $customer->id, 'vehicle_id' => $this->makeVehicle($customer)->id,
            'assign_to' => $tech->id, 'created_by' => $tech->id,
            'title' => 'S4', 'service_date' => now()->toDateString(), 'charge' => 75000,
        ]);

        $report = app(ReportService::class)->serviceReport([]);

        $byTech = collect($report['by_technician']);
        $row = $byTech->firstWhere('technician_id', $tech->id);
        $this->assertNotNull($row);
        $this->assertSame('Teknisi Uji', $row['technician_name']);
        $this->assertSame(75000, (int) $row['revenue']);
    }

    private function makeVehicle(Customer $customer): Vehicle
    {
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => 'mpv-'.uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);
        $fuel = FuelType::firstOrCreate(['slug' => 'bensin'], ['fuel_type' => 'Bensin']);

        return Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'H '.random_int(1000, 9999).' '.chr(random_int(65, 90)).chr(random_int(65, 90)),
            'branch_id' => $customer->branch_id,
        ]);
    }
}
