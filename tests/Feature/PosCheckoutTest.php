<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
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

    private function makeUser(string $role = 'kasir'): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_pos_checkout_creates_invoice_items_payment_stock_history_and_income(): void
    {
        $kasir = $this->makeUser('kasir');
        $this->actingAs($kasir);

        $branch = Branch::create(['name' => 'Cabang Pusat', 'is_active' => true]);
        $type = ProductType::create(['type' => 'Sparepart', 'slug' => 'sparepart', 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs', 'is_active' => true]);

        $product = Product::create([
            'product_no' => 'P-001',
            'code' => 'OIL-001',
            'barcode' => '8990001',
            'name' => 'Oli Mesin 1L',
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 50000,
            'cost_price' => 40000,
            'branch_id' => $branch->id,
        ]);

        StockRecord::create([
            'product_id' => $product->id,
            'quantity' => 10,
            'minimum_stock' => 2,
            'branch_id' => $branch->id,
        ]);

        $paymentMethod = PaymentMethod::create([
            'payment' => 'Cash',
            'slug' => 'cash',
            'is_active' => true,
        ]);

        // Open POS session
        $response = $this->withSession(['current_branch_id' => $branch->id])
            ->post('/pos/open', ['opening_balance' => 100000]);

        $response->assertRedirect(route('pos.terminal'));

        $session = PosSession::withoutGlobalScopes()->where('user_id', $kasir->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('open', $session->status);
        $this->assertEquals(100000, (float) $session->opening_balance);
        $this->assertEquals($branch->id, $session->branch_id);

        // Search product
        $search = $this->get('/pos/search-product?q=Oli');
        $search->assertOk();
        $this->assertCount(1, $search->json());
        $this->assertEquals('Oli Mesin 1L', $search->json('0.name'));
        $this->assertEquals(10, $search->json('0.stock'));

        // Checkout 2 pcs @ 50.000 = 100.000
        $response = $this->withSession(['current_branch_id' => $branch->id])
            ->post('/pos/checkout', [
                'session_id' => $session->id,
                'customer_id' => null,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'unit_price' => 50000,
                        'discount' => 0,
                        'discount_type' => null,
                    ],
                ],
                'discount' => 0,
                'payments' => [
                    ['method_id' => $paymentMethod->id, 'amount' => 100000],
                ],
            ]);

        $response->assertRedirect();

        // Invoice created with invoice_type = 'pos'
        $invoice = Invoice::withoutGlobalScopes()->where('pos_session_id', $session->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('pos', $invoice->invoice_type);
        $this->assertEquals(100000, (float) $invoice->grand_total);
        $this->assertEquals(2, (int) $invoice->payment_status);

        // InvoiceItem created
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // PaymentRecord created
        $this->assertDatabaseHas('payment_records', [
            'invoice_id' => $invoice->id,
            'amount' => 100000,
        ]);

        // StockRecord decremented 10 -> 8
        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
        $this->assertEquals(8, $stock->quantity);

        // StockHistory created
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'type' => 'pos',
            'quantity_change' => -2,
            'new_stock' => 8,
        ]);

        // Income record created
        $this->assertDatabaseHas('incomes', [
            'invoice_number' => $invoice->invoice_number,
        ]);

        $income = Income::withoutGlobalScopes()->where('invoice_number', $invoice->invoice_number)->first();
        $this->assertEquals(100000, (float) $income->amount);
    }
}
