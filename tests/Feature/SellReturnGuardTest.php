<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventRequestForgery;
use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\StockRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Retur tidak boleh melebihi sisa yang dapat diretur dari transaksi asli
 * (guard ada di SellReturnController@store; test ini menguncinya).
 */
class SellReturnGuardTest extends TestCase
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

    private function actingUser(): User
    {
        Role::findOrCreate('custom_retur', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('custom_retur');
        $user->givePermissionTo([
            Permission::findOrCreate('sale.view', 'web'),
            Permission::findOrCreate('sale.create', 'web'),
        ]);
        $branch = Branch::create(['name' => 'Cabang R '.uniqid(), 'code' => 'R'.uniqid(), 'is_active' => true]);
        $user->branches()->sync([$branch->id]);
        $this->actingAs($user);

        return $user;
    }

    private function makeProduct(): Product
    {
        $type = ProductType::create(['type' => 'T'.uniqid(), 'slug' => uniqid(), 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'N'.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true]);
        $product = Product::create([
            'product_no' => 'P-'.uniqid(), 'code' => 'C-'.uniqid(), 'name' => 'Part Tes',
            'product_type_id' => $type->id, 'unit_id' => $unit->id,
            'price' => 50000, 'cost_price' => 40000,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 10]);

        return $product;
    }

    public function test_return_more_than_sold_is_rejected(): void
    {
        $user = $this->actingUser();
        $customer = Customer::create(['name' => 'CR', 'phone' => '0813000001']);
        $product = $this->makeProduct();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-'.uniqid(), 'customer_id' => $customer->id,
            'invoice_type' => 'service', 'invoice_date' => now()->toDateString(),
            'total_amount' => 100000, 'grand_total' => 100000,
            'created_by' => $user->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'product_id' => $product->id,
            'quantity' => 2, 'unit_price' => 50000, 'total_price' => 100000,
        ]);

        $payload = [
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Cacat',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ];

        $this->post(route('sell-returns.store'), $payload)
            ->assertSessionHas('error');

        $this->assertDatabaseCount('sell_returns', 0);
    }

    public function test_return_exact_quantity_then_second_return_rejected(): void
    {
        $user = $this->actingUser();
        $customer = Customer::create(['name' => 'CR2', 'phone' => '0813000002']);
        $product = $this->makeProduct();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-'.uniqid(), 'customer_id' => $customer->id,
            'invoice_type' => 'service', 'invoice_date' => now()->toDateString(),
            'total_amount' => 100000, 'grand_total' => 100000,
            'created_by' => $user->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'product_id' => $product->id,
            'quantity' => 2, 'unit_price' => 50000, 'total_price' => 100000,
        ]);

        $payload = [
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Cacat',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $this->post(route('sell-returns.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('sell_returns', 1);

        // Sisa sudah 0 — retur susulan harus ditolak.
        $this->post(route('sell-returns.store'), $payload)
            ->assertSessionHas('error');
        $this->assertDatabaseCount('sell_returns', 1);
    }
}
