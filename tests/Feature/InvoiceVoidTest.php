<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\StockRecord;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fitur void invoice ber-payment (D2 opsi A): stok kembali, jurnal
 * AR+HPP+pembayaran di-reverse, refund tercatat, riwayat dipertahankan.
 */
class InvoiceVoidTest extends TestCase
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
        $branch = Branch::create(['name' => 'Cabang Uji '.uniqid(), 'code' => 'T'.uniqid(), 'is_active' => true]);
        $user->branches()->sync([$branch->id]);
        $this->actingAs($user);

        return $user;
    }

    private function seedAccounts(): void
    {
        foreach ([
            ['1000', 'Cash', 'asset'],
            ['1100', 'Accounts Receivable', 'asset'],
            ['1400', 'Inventory', 'asset'],
            ['4000', 'Parts Revenue', 'income'],
            ['4100', 'Service Revenue', 'income'],
            ['5000', 'Cost of Goods Sold', 'expense'],
        ] as [$code, $name, $type]) {
            ChartOfAccount::create(['code' => $code.$code, 'name' => $name, 'type' => $type, 'is_active' => true]);
        }
        PaymentMethod::create(['payment' => 'Cash', 'slug' => 'cash-'.uniqid(), 'is_active' => true]);
    }

    private function makeStockedProduct(): Product
    {
        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Kampas Rem',
            'product_type_id' => ProductType::create(['type' => 'T'.uniqid(), 'slug' => uniqid(), 'is_active' => true])->id,
            'unit_id' => ProductUnit::create(['name' => 'N'.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true])->id,
            'price' => 100000,
            'cost_price' => 60000,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 10]);

        return $product;
    }

    private function makeInvoice(Product $product): Invoice
    {
        return app(InvoiceService::class)->create([
            'customer_id' => Customer::create(['name' => 'Cust Void', 'phone' => '0817'.random_int(10000000, 99999999)])->id,
            'invoice_type' => 'sales_part',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'description' => 'Kampas Rem', 'quantity' => 2, 'unit_price' => 100000],
            ],
        ]);
    }

    private function stockOf(Product $product): float
    {
        return (float) StockRecord::where('product_id', $product->id)->firstOrFail()->quantity;
    }

    public function test_void_unpaid_restores_stock_and_reverses_journals(): void
    {
        $this->actingAsRole('custom_admin', ['invoice.view', 'invoice.void']);
        $this->seedAccounts();
        $product = $this->makeStockedProduct();
        $invoice = $this->makeInvoice($product);
        $this->assertSame(8.0, $this->stockOf($product));

        $this->post(route('invoices.void', $invoice), ['reason' => 'Batal oleh customer'])
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertTrue($invoice->isVoided());
        $this->assertSame('Batal oleh customer', $invoice->void_reason);
        $this->assertSame(10.0, $this->stockOf($product));

        // Jurnal AR + HPP di-reverse, tanpa refund (belum ada pembayaran).
        $this->assertTrue(JournalEntry::where('reference_type', Invoice::class)->where('reference_id', $invoice->id)->where('entry_type', 'ar_invoice_reversal')->exists());
        $this->assertTrue(JournalEntry::where('reference_type', Invoice::class)->where('reference_id', $invoice->id)->where('entry_type', 'cogs_reversal')->exists());
        $this->assertSame(0, PaymentRecord::where('invoice_id', $invoice->id)->count());

        $this->get(route('invoices.show', $invoice))->assertOk()->assertSee('Void');
    }

    public function test_void_paid_records_refund_and_blocks_further_payment(): void
    {
        $this->actingAsRole('custom_kasir', ['invoice.view', 'invoice.void', 'payment.process', 'invoice.edit']);
        $this->seedAccounts();
        $product = $this->makeStockedProduct();
        $invoice = $this->makeInvoice($product);

        app(PaymentService::class)->process($invoice, [
            'amount' => 200000,
            'payment_method_id' => PaymentMethod::firstOrFail()->id,
            'payment_date' => now()->toDateTimeString(),
        ]);
        $this->assertSame(2, (int) $invoice->fresh()->payment_status);

        $this->post(route('invoices.void', $invoice), ['reason' => 'Barang cacat, dana kembali'])
            ->assertRedirect();

        // Refund tercatat sebagai payment negatif + income offset.
        $this->assertDatabaseHas('payment_records', [
            'invoice_id' => $invoice->id,
            'amount' => -200000,
            'reference_number' => 'VOID-'.$invoice->id,
        ]);
        $this->assertDatabaseHas('incomes', [
            'invoice_number' => $invoice->invoice_number,
            'amount' => -200000,
        ]);
        // Jurnal pembayaran di-reverse.
        $this->assertTrue(JournalEntry::where('entry_type', 'payment_reversal')->exists());
        $this->assertSame(10.0, $this->stockOf($product));

        // Pembayaran lanjutan diblokir, hapus fisik diblokir.
        $this->get(route('payments.create', $invoice))->assertForbidden();
        $this->delete(route('invoices.destroy', $invoice))->assertForbidden();
    }

    public function test_double_void_rejected(): void
    {
        $this->actingAsRole('custom_admin', ['invoice.view', 'invoice.void']);
        $this->seedAccounts();
        $invoice = $this->makeInvoice($this->makeStockedProduct());

        $this->post(route('invoices.void', $invoice), ['reason' => 'Pertama'])->assertRedirect();
        $this->post(route('invoices.void', $invoice), ['reason' => 'Kedua'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('Pertama', $invoice->fresh()->void_reason);
    }

    public function test_void_requires_permission(): void
    {
        $this->actingAsRole('custom_viewer', ['invoice.view']);
        $this->seedAccounts();
        $invoice = $this->makeInvoice($this->makeStockedProduct());

        $this->post(route('invoices.void', $invoice), ['reason' => 'Nekat'])->assertForbidden();
        $this->assertFalse($invoice->fresh()->isVoided());
    }
}
