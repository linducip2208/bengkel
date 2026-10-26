<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MediaAttachment;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Service;
use App\Models\StockRecord;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bukti bahwa edit di /roles berdampak: otorisasi kini membaca permission
 * Spatie, bukan nama role yang di-hardcode.
 */
class RolePermissionEnforcementTest extends TestCase
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

    private function actingAsRole(string $role, array $permissions = []): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $models = [];
        foreach ($permissions as $permission) {
            $models[] = Permission::findOrCreate($permission, 'web');
        }
        if ($models) {
            $user->givePermissionTo($models);
        }
        if (! in_array($role, ['super_admin', 'admin'], true)) {
            $branch = Branch::create(['name' => 'Cabang Uji '.uniqid(), 'code' => 'T'.uniqid(), 'is_active' => true]);
            $user->branches()->sync([$branch->id]);
        }
        $this->actingAs($user);

        return $user;
    }

    public function test_role_permission_edit_controls_invoice_creation(): void
    {
        $user = $this->actingAsRole('custom_tester');

        $branchId = $user->branches()->first()?->id;
        $payload = [
            'customer_id' => Customer::create(['name' => 'C', 'phone' => '081', 'branch_id' => $branchId])->id,
            'invoice_type' => 'service',
            'invoice_date' => now()->toDateString(),
            'items' => [['description' => 'Jasa', 'quantity' => 1, 'unit_price' => 10000]],
        ];

        // Tanpa permission: ditolak.
        $this->post(route('invoices.store'), $payload)->assertForbidden();

        // Beri permission via role (simulasi edit di /roles): lolos gate.
        $models = [];
        foreach (['invoice.create', 'invoice.edit'] as $p) {
            $models[] = Permission::findOrCreate($p, 'web');
        }
        $user->givePermissionTo($models);
        $this->post(route('invoices.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('invoices', 1);

        // Cabut lagi: kembali ditolak.
        $user->revokePermissionTo('invoice.create', 'invoice.edit');
        $this->get(route('invoices.index'))->assertForbidden();
    }

    public function test_kasir_cannot_give_pos_discount_but_manager_can(): void
    {
        $kasir = $this->actingAsRole('kasir', ['pos.view', 'pos.checkout']);
        $this->assertFalse($kasir->can('pos.discount'));

        $manager = $this->actingAsRole('manager', ['pos.discount']);
        $this->assertTrue($manager->can('pos.discount'));
    }

    public function test_estimates_override_gate_follows_permission_not_role_name(): void
    {
        // Role custom dengan permission override: lolos gate walau bukan manager.
        $user = $this->actingAsRole('custom_override', ['estimates.override']);
        $this->assertTrue($user->can('estimates.override'));

        $user->revokePermissionTo('estimates.override');
        $this->assertFalse($user->fresh()->can('estimates.override'));
    }

    public function test_multi_permission_route_allows_any_listed_permission(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('lampiran/tes.txt', 'isi');

        // Tanpa permission relevan: 403.
        $nobody = $this->actingAsRole('custom_none', []);
        $media = MediaAttachment::create([
            'attachable_type' => Service::class,
            'attachable_id' => 1,
            'name' => 'tes.txt',
            'file_path' => 'lampiran/tes.txt',
            'mime_type' => 'text/plain',
            'size' => 4,
            'uploaded_by' => $nobody->id,
        ]);
        $this->get(route('attachments.download', $media))->assertForbidden();

        // Satu saja dari daftar (jobcard.view) cukup.
        $this->actingAsRole('custom_partial', ['jobcard.view']);
        $this->get(route('attachments.download', $media))->assertOk();
    }

    public function test_global_search_only_returns_permitted_modules(): void
    {
        // Kasir yang hanya boleh lihat produk + customer: tidak boleh
        // mengintip service/invoice lewat pencarian global.
        $user = $this->actingAsRole('custom_search', ['product.view', 'customer.view']);
        $branchId = $user->branches()->firstOrFail()->id;
        Customer::create(['name' => 'Cari Saya Khusus', 'phone' => '0811', 'branch_id' => $branchId]);
        $res = $this->getJson('/search?q=Cari');
        $res->assertOk();
        $this->assertSame([], $res->json('services'));
        $this->assertSame([], $res->json('invoices'));
        $this->assertNotEmpty($res->json('customers'));
    }

    public function test_pos_checkout_rejects_discount_without_pos_discount_permission(): void
    {
        $kasir = $this->actingAsRole('custom_pos', ['pos.view', 'pos.open', 'pos.close', 'pos.checkout']);
        $branch = Branch::firstOrFail();
        $this->withSession(['current_branch_id' => $branch->id]);

        $type = ProductType::create(['type' => 'T'.uniqid(), 'slug' => uniqid(), 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'N'.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true]);
        $product = Product::create([
            'product_no' => 'P-'.uniqid(), 'code' => 'C-'.uniqid(), 'name' => 'Oli Tes',
            'product_type_id' => $type->id, 'unit_id' => $unit->id,
            'price' => 50000, 'cost_price' => 40000, 'branch_id' => $branch->id,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 10, 'branch_id' => $branch->id]);
        $method = PaymentMethod::create(['payment' => 'Cash', 'slug' => 'cash-'.uniqid(), 'is_active' => true]);

        $this->post('/pos/open', ['opening_balance' => 100000])->assertRedirect();
        $session = PosSession::where('user_id', $kasir->id)->firstOrFail();

        // Diskon tanpa pos.discount: 403, tidak ada invoice yang terbentuk.
        $this->post('/pos/checkout', [
            'session_id' => $session->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50000, 'discount' => 5000]],
            'discount' => 0,
            'payments' => [['method_id' => $method->id, 'amount' => 50000]],
        ])->assertForbidden();
        $this->assertDatabaseCount('invoices', 0);
    }
}
