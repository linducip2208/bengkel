<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            RequirePair::class,
            PreventRequestForgery::class,
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

        // Client mencoba memalsukan harga menjadi Rp1; server wajib memakai
        // harga produk Rp50.000 sehingga total tetap Rp100.000.
        $response = $this->withSession(['current_branch_id' => $branch->id])
            ->post('/pos/checkout', [
                'session_id' => $session->id,
                'customer_id' => null,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'unit_price' => 1,
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
        $this->assertEquals(50000, (float) $invoice->items()->firstOrFail()->unit_price);
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

    public function test_pos_caps_split_payments_at_invoice_total_and_keeps_tendered_amount(): void
    {
        $kasir = $this->makeUser('kasir');
        $this->actingAs($kasir);

        $branch = Branch::create(['name' => 'Cabang Split', 'is_active' => true]);
        $type = ProductType::create(['type' => 'Oli', 'slug' => 'oli-split', 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Liter', 'abbreviation' => 'ltr', 'is_active' => true]);
        $product = Product::create([
            'product_no' => 'P-SPLIT', 'code' => 'SPLIT-001', 'name' => 'Oli Split',
            'product_type_id' => $type->id, 'unit_id' => $unit->id,
            'price' => 100000, 'cost_price' => 70000, 'branch_id' => $branch->id,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 5, 'branch_id' => $branch->id]);

        $cash = PaymentMethod::create(['payment' => 'Cash Split', 'slug' => 'cash-split', 'is_active' => true]);
        $card = PaymentMethod::create(['payment' => 'Card Split', 'slug' => 'card-split', 'is_active' => true]);

        $this->withSession(['current_branch_id' => $branch->id])->post('/pos/open', ['opening_balance' => 0]);
        $session = PosSession::withoutGlobalScopes()->where('user_id', $kasir->id)->firstOrFail();

        $response = $this->withSession(['current_branch_id' => $branch->id])->post('/pos/checkout', [
            'session_id' => $session->id,
            'items' => [[
                'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100000,
                'discount' => 0,
            ]],
            'payments' => [
                ['method_id' => $cash->id, 'amount' => 60000],
                ['method_id' => $card->id, 'amount' => 50000],
            ],
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertNull(session('error'), (string) session('error'));
        $response->assertRedirect();

        $invoice = Invoice::withoutGlobalScopes()->where('pos_session_id', $session->id)->firstOrFail();

        $this->assertSame(100000.0, (float) $invoice->paid_amount);
        $this->assertSame(110000.0, (float) $invoice->amount_received);
        $this->assertSame(100000.0, (float) PaymentRecord::where('invoice_id', $invoice->id)->sum('amount'));
        $this->assertDatabaseHas('payment_records', [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $card->id,
            'amount' => 40000,
        ]);
    }

    public function test_api_pos_cannot_open_session_for_unassigned_branch(): void
    {
        $kasir = $this->makeUser('kasir');
        $allowed = Branch::create(['name' => 'Cabang Diizinkan', 'is_active' => true]);
        $forbidden = Branch::create(['name' => 'Cabang Terlarang', 'is_active' => true]);
        $kasir->branches()->attach($allowed->id);
        $token = $kasir->createToken('pos-branch-test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/pos/open', [
                'opening_balance' => 0,
                'branch_id' => $forbidden->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, PosSession::withoutGlobalScopes()->count());
    }
}
