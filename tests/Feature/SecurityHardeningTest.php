<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventRequestForgery;
use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Invoice;
use App\Models\MediaAttachment;
use App\Models\PaymentMethod;
use App\Models\PosSession;
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
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
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

    public function test_inactive_user_blocked_web_and_api(): void
    {
        Role::findOrCreate('kasir', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('kasir');
        $branch = Branch::create(['name' => 'Cabang Uji '.uniqid(), 'code' => 'T'.uniqid(), 'is_active' => true]);
        $user->branches()->sync([$branch->id]);

        $token = $user->createToken('t')->plainTextToken;
        $user->update(['is_active' => false]);

        // API: token milik user nonaktif harus 401.
        $this->withToken($token)->getJson('/api/v1/master-data')->assertUnauthorized();

        // Web: sesi milik user nonaktif harus dialihkan ke login.
        $this->actingAs($user->fresh());
        $this->get('/pos/terminal')->assertRedirect(route('login'));
    }

    public function test_print_unregistered_ip_rejected(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $customer = Customer::create(['name' => 'Cetak Uji']);
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => 'mpv-'.uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);
        $fuel = FuelType::create(['fuel_type' => 'Pertamax', 'slug' => 'pertamax-'.uniqid()]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B 1234 ABC',
            'model_name' => 'Avanza',
            'model_year' => 2020,
            'odometer' => 50000,
        ]);
        $category = RepairCategory::create([
            'repair_category_name' => 'Tune Up',
            'slug' => 'tune-up-'.uniqid(),
            'is_active' => true,
        ]);
        $service = Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'title' => 'Servis Cetak',
            'service_date' => now(),
            'created_by' => $admin->id,
            'job_no' => 'BP-'.uniqid(),
        ]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-'.uniqid(),
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'invoice_type' => 'service',
            'invoice_date' => now()->toDateString(),
            'grand_total' => 10000,
        ]);

        // IP+port yang tidak terdaftar di tabel printers harus ditolak 422 (anti-SSRF).
        $this->post(route('print.invoice', $invoice), [
            'printer_ip' => '169.254.169.254',
            'printer_port' => 80,
        ])->assertStatus(422);
    }

    public function test_media_download_cross_branch_404(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('lampiran/tes.txt', 'isi');

        Role::findOrCreate('custom_cabang', 'web');
        $branchA = Branch::create(['name' => 'Cabang A '.uniqid(), 'code' => 'A'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Cabang B '.uniqid(), 'code' => 'B'.uniqid(), 'is_active' => true]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('custom_cabang');
        $user->givePermissionTo(Permission::findOrCreate('service.view', 'web'));
        $user->branches()->sync([$branchA->id]);

        $customerB = Customer::create(['name' => 'Pelanggan B', 'phone' => '0811', 'branch_id' => $branchB->id]);
        $media = MediaAttachment::create([
            // store() menulis FQCN lewat relasi morph — tiru data produksi.
            'attachable_type' => Customer::class,
            'attachable_id' => $customerB->id,
            'name' => 'tes.txt',
            'file_path' => 'lampiran/tes.txt',
            'mime_type' => 'text/plain',
            'size' => 4,
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user);
        $this->withSession(['current_branch_id' => $branchA->id]);
        $this->get(route('attachments.download', $media))->assertNotFound();
    }

    public function test_pos_checkout_double_submit_single_invoice(): void
    {
        Role::findOrCreate('kasir', 'web');
        $kasir = User::factory()->create(['is_active' => true]);
        $kasir->assignRole('kasir');
        PermissionSeeder::grantProductionPermissions($kasir);
        $this->actingAs($kasir);

        $branch = Branch::create(['name' => 'Cabang Kasir '.uniqid(), 'code' => 'T'.uniqid(), 'is_active' => true]);
        $kasir->branches()->sync([$branch->id]);

        $type = ProductType::create(['type' => 'Sparepart', 'slug' => 'sparepart-'.uniqid(), 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs-'.uniqid(), 'is_active' => true]);
        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Oli Tes',
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 50000,
            'cost_price' => 40000,
            'branch_id' => $branch->id,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 10, 'branch_id' => $branch->id]);
        $method = PaymentMethod::create(['payment' => 'Cash', 'slug' => 'cash-'.uniqid(), 'is_active' => true]);

        $this->withSession(['current_branch_id' => $branch->id])
            ->post('/pos/open', ['opening_balance' => 100000])
            ->assertRedirect();
        $session = PosSession::where('user_id', $kasir->id)->firstOrFail();

        $payload = [
            'session_id' => $session->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 50000,
                'discount' => 0,
            ]],
            'discount' => 0,
            'payments' => [['method_id' => $method->id, 'amount' => 50000]],
            'idempotency_key' => 'idem-'.uniqid(),
        ];

        $first = $this->withSession(['current_branch_id' => $branch->id])->post('/pos/checkout', $payload);
        $first->assertRedirect();
        $second = $this->withSession(['current_branch_id' => $branch->id])->post('/pos/checkout', $payload);

        $this->assertDatabaseCount('invoices', 1);
        $this->assertNotEquals(500, $second->getStatusCode());
    }
}
