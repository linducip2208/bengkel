<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use Database\Seeders\PermissionSeeder;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class]);
    }

    private function makeUser(string $role, ?Branch $branch = null): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate($role, 'web'));
        PermissionSeeder::grantProductionPermissions($user);
        if ($branch && ! in_array($role, ['super_admin', 'admin'], true)) {
            $user->branches()->sync([$branch->id]);
        }

        return $user;
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'victim@test.id', 'is_active' => true]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/login', ['email' => 'victim@test.id', 'password' => 'wrong'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/v1/login', ['email' => 'victim@test.id', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_client_branch_id_is_ignored_and_cannot_escape(): void
    {
        $branchA = Branch::create(['name' => 'A', 'code' => 'A-'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'B', 'code' => 'B-'.uniqid(), 'is_active' => true]);

        $seeder = $this->makeUser('admin');
        Sanctum::actingAs($seeder, ['*']);
        Customer::create(['name' => 'Milik A', 'phone' => '0810000001', 'branch_id' => $branchA->id]);

        $userB = $this->makeUser('manager', $branchB);
        Sanctum::actingAs($userB, ['*']);

        // Spoofed branch_id must not divert the record into branch A.
        $res = $this->postJson('/api/v1/customers', [
            'name' => 'Baru', 'phone' => '0810000002', 'branch_id' => $branchA->id,
        ])->assertCreated();

        $this->assertSame($branchB->id, Customer::find($res->json('id'))->branch_id);

        // Branch A's customer must not leak into branch B's list.
        $list = $this->getJson('/api/v1/customers')->assertOk()->json('data');
        foreach ($list as $row) {
            $this->assertNotSame('Milik A', $row['name']);
        }
    }

    public function test_estimates_index_has_no_n_plus_one(): void
    {
        $admin = $this->makeUser('admin');
        Sanctum::actingAs($admin, ['*']);

        $customer = Customer::create(['name' => 'C', 'phone' => '0819']);
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => 'mpv-'.uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);

        for ($i = 0; $i < 5; $i++) {
            $vehicle = Vehicle::create([
                'customer_id' => $customer->id,
                'vehicle_type_id' => $type->id,
                'vehicle_brand_id' => $brand->id,
                'number_plate' => 'B '.mt_rand(1000, 9999).' T'.$i,
            ]);
            $service = Service::create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'title' => 'Svc '.$i,
                'service_date' => now()->toDateString(),
                'created_by' => $admin->id,
            ]);
            ServiceEstimate::create([
                'service_id' => $service->id,
                'estimate_number' => 'EST-N-'.$i.'-'.uniqid(),
                'status' => 'draft',
                'subtotal' => 0, 'discount_total' => 0, 'tax_total' => 0, 'grand_total' => 0,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/v1/estimates?per_page=20')->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(25, $queries, "N+1 suspected: {$queries} queries for 5 estimates");
    }

    public function test_api_answers_options_with_cors_headers(): void
    {
        $response = $this->call('OPTIONS', '/api/v1/health', [], [], [], [
            'HTTP_ORIGIN' => 'https://app.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        $response->assertSuccessful();
        $this->assertTrue(
            $response->headers->has('Access-Control-Allow-Origin'),
            'CORS headers missing on API preflight'
        );
    }
}
