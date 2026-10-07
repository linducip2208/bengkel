<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FleetNotificationToken;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiWorkshopContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class]);
    }

    private function makeService(Customer $customer, ?int $branchId = null): Service
    {
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => 'mpv-'.uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'number_plate' => 'B '.mt_rand(1000, 9999).' XYZ',
            'branch_id' => $branchId,
        ]);

        return Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'branch_id' => $branchId,
            'title' => 'Svc '.uniqid(),
            'service_date' => now()->toDateString(),
            'created_by' => auth()->id() ?? User::factory()->create(['is_active' => true])->id,
        ]);
    }

    private function makeUser(string $role, ?Branch $branch = null): User
    {
        $roleModel = Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($roleModel);
        if ($branch && ! in_array($role, ['super_admin', 'admin'], true)) {
            $user->branches()->sync([$branch->id]);
        }

        return $user;
    }

    public function test_health_is_public(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_workshop_endpoints_require_auth(): void
    {
        $this->getJson('/api/v1/estimates')->assertUnauthorized();
        $this->getJson('/api/v1/work-packages')->assertUnauthorized();
        $this->getJson('/api/v1/work-tasks')->assertUnauthorized();
        $this->getJson('/api/v1/findings')->assertUnauthorized();
        $this->getJson('/api/v1/branches')->assertUnauthorized();
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_estimates_index_branch_isolated(): void
    {
        $branchA = Branch::create(['name' => 'A', 'code' => 'A-'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'B', 'code' => 'B-'.uniqid(), 'is_active' => true]);

        $seeder = $this->makeUser('admin');
        Sanctum::actingAs($seeder, ['*']);

        $customer = Customer::create(['name' => 'C', 'phone' => '0811', 'branch_id' => $branchA->id]);
        $serviceA = $this->makeService($customer, $branchA->id);
        ServiceEstimate::create([
            'service_id' => $serviceA->id,
            'branch_id' => $branchA->id,
            'estimate_number' => 'EST-A-1',
            'status' => 'draft',
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
        ]);

        $userB = $this->makeUser('manager', $branchB);
        Sanctum::actingAs($userB, ['*']);

        $res = $this->getJson('/api/v1/estimates');
        $res->assertOk();
        $this->assertSame(0, $res->json('total'));
    }

    public function test_branches_endpoint_only_returns_accessible(): void
    {
        $branchA = Branch::create(['name' => 'A', 'code' => 'A-'.uniqid(), 'is_active' => true]);
        Branch::create(['name' => 'B', 'code' => 'B-'.uniqid(), 'is_active' => true]);

        $user = $this->makeUser('manager', $branchA);
        Sanctum::actingAs($user, ['*']);

        $res = $this->getJson('/api/v1/branches')->assertOk();
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertSame([$branchA->id], $ids);
    }

    public function test_task_transition_guards_illegal_state(): void
    {
        $admin = $this->makeUser('admin');
        Sanctum::actingAs($admin, ['*']);

        $customer = Customer::create(['name' => 'C', 'phone' => '0812']);
        $service = $this->makeService($customer);
        $package = ServiceWorkPackage::create([
            'service_id' => $service->id,
            'branch_id' => $service->branch_id,
            'title' => 'Paket uji',
            'status' => 'draft',
        ]);
        $task = ServiceWorkTask::create([
            'service_id' => $service->id,
            'service_work_package_id' => $package->id,
            'branch_id' => $service->branch_id,
            'status' => 'pending',
        ]);

        // pending -> in_progress is legal.
        $this->postJson("/api/v1/work-tasks/{$task->id}/start", [])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        // completed tasks cannot be restarted.
        $task->fresh()->forceFill(['status' => 'completed'])->save();
        $this->postJson("/api/v1/work-tasks/{$task->id}/start", [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Task tidak bisa dimulai dari status completed.');
    }

    public function test_vehicle_image_upload_and_delete(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');
        Sanctum::actingAs($admin, ['*']);

        $customer = Customer::create(['name' => 'C', 'phone' => '0813']);
        $service = $this->makeService($customer);
        $vehicleId = $service->vehicle_id;

        $res = $this->call('POST', "/api/v1/vehicles/{$vehicleId}/images", ['caption' => 'Depan'], [], [
            'image' => UploadedFile::fake()->image('foto.jpg'),
        ], ['HTTP_ACCEPT' => 'application/json']);
        $res->assertCreated()->assertJsonPath('data.caption', 'Depan');
        $this->assertNotEmpty($res->json('data.url'));
        Storage::disk('public')->assertExists($res->json('data.image_url'));

        $imageId = $res->json('data.id');
        $this->deleteJson("/api/v1/vehicles/{$vehicleId}/images/{$imageId}")->assertOk();
        Storage::disk('public')->assertMissing($res->json('data.image_url'));
    }

    public function test_vehicle_image_upload_rejects_non_image(): void
    {
        $admin = $this->makeUser('admin');
        Sanctum::actingAs($admin, ['*']);

        $customer = Customer::create(['name' => 'C', 'phone' => '0814']);
        $service = $this->makeService($customer);

        $this->call('POST', "/api/v1/vehicles/{$service->vehicle_id}/images", [], [], [
            'image' => UploadedFile::fake()->create('jahat.php', 100, 'application/x-php'),
        ], ['HTTP_ACCEPT' => 'application/json'])->assertStatus(422);
    }

    public function test_vehicle_image_cross_branch_404(): void
    {
        Storage::fake('public');
        $branchA = Branch::create(['name' => 'A', 'code' => 'A-'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'B', 'code' => 'B-'.uniqid(), 'is_active' => true]);

        $seeder = $this->makeUser('admin');
        Sanctum::actingAs($seeder, ['*']);
        $customer = Customer::create(['name' => 'C', 'phone' => '0815', 'branch_id' => $branchA->id]);
        $service = $this->makeService($customer, $branchA->id);

        $upload = $this->call('POST', "/api/v1/vehicles/{$service->vehicle_id}/images", [], [], [
            'image' => UploadedFile::fake()->image('a.jpg'),
        ], ['HTTP_ACCEPT' => 'application/json'])->assertCreated();
        $imageId = $upload->json('data.id');

        $userB = $this->makeUser('manager', $branchB);
        Sanctum::actingAs($userB, ['*']);

        // Parent vehicle is invisible cross-branch, so nested image routes 404.
        $this->call('POST', "/api/v1/vehicles/{$service->vehicle_id}/images", [], [], [
            'image' => UploadedFile::fake()->image('b.jpg'),
        ], ['HTTP_ACCEPT' => 'application/json'])->assertNotFound();
        $this->deleteJson("/api/v1/vehicles/{$service->vehicle_id}/images/{$imageId}")->assertNotFound();
    }

    public function test_device_token_register_and_unregister(): void
    {
        $user = $this->makeUser('mekanik');
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/device-tokens', ['token' => 'fcm-123', 'platform' => 'android'])
            ->assertCreated();
        $this->assertDatabaseHas('fleet_notification_tokens', [
            'user_id' => $user->id, 'token' => 'fcm-123', 'is_active' => true,
        ]);

        // Re-register is idempotent (no duplicate row).
        $this->postJson('/api/v1/device-tokens', ['token' => 'fcm-123', 'platform' => 'android'])
            ->assertCreated();
        $this->assertSame(1, FleetNotificationToken::where('user_id', $user->id)->where('token', 'fcm-123')->count());

        // Cannot unregister another user's token.
        $other = $this->makeUser('kasir');
        FleetNotificationToken::create(['user_id' => $other->id, 'token' => 'fcm-x', 'is_active' => true]);
        $this->deleteJson('/api/v1/device-tokens', ['token' => 'fcm-x'])->assertOk();
        $this->assertDatabaseHas('fleet_notification_tokens', ['user_id' => $other->id, 'token' => 'fcm-x']);
    }
}
