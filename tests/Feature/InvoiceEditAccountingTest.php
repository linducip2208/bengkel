<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\StockRecord;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceEditAccountingTest extends TestCase
{
    use RefreshDatabase;

    // Default accounts that getDefaultAccount() resolves to.
    protected function seedAccounts(): void
    {
        foreach ([
            ['1100', 'Accounts Receivable', 'asset'],
            ['1200', 'Inventory', 'asset'],
            ['4000', 'Service Revenue', 'income'],
            ['4100', 'Parts Revenue', 'income'],
            ['5100', 'Cost of Goods Sold', 'expense'],
        ] as [$code, $name, $type]) {
            ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'is_active' => true]);
        }
    }

    private function makeProduct(): Product
    {
        $type = ProductType::create(['type' => 'Sparepart', 'slug' => uniqid(), 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs', 'is_active' => true]);
        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Part '.uniqid(),
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 100000,
            'cost_price' => 50000,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 10]);

        return $product;
    }

    private function totalEntryLines(string $type, int $invoiceId, bool $debit): float
    {
        $entry = JournalEntry::where('reference_type', Invoice::class)
            ->where('reference_id', $invoiceId)
            ->where('entry_type', $type)
            ->first();

        return $entry ? round((float) JournalEntryLine::where('journal_entry_id', $entry->id)->sum($debit ? 'debit' : 'credit'), 2) : 0.0;
    }

    public function test_editing_an_unpaid_invoice_realigns_ar_and_cogs_journal(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->seedAccounts();
        $product = $this->makeProduct();
        $customer = Customer::create(['name' => 'Invoice Edit', 'phone' => '0818888888']);

        $invoice = app(InvoiceService::class)->create([
            'customer_id' => $customer->id,
            'invoice_type' => 'service',
            'invoice_date' => now()->toDateString(),
            'notes' => 'Draft',
            'items' => [
                ['product_id' => $product->id, 'description' => 'Part', 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0, 'discount_type' => 'fixed'],
                ['product_id' => null, 'description' => 'Jasa servis', 'quantity' => 1, 'unit_price' => 50000, 'discount' => 0, 'discount_type' => 'fixed'],
            ],
        ]);

        $this->assertEquals(150000.0, (float) $invoice->grand_total);
        $this->assertEquals(1, $this->countEntries($invoice->id, 'ar_invoice'));
        $this->assertEquals(150000.0, $this->totalEntryLines('ar_invoice', $invoice->id, true));
        $this->assertEquals(50000.0, $this->totalEntryLines('cogs', $invoice->id, true));
        $this->assertEquals(9, (int) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity'));

        // Double the part quantity: 2×100.000 parts + 50.000 labor = 250.000.
        app(InvoiceService::class)->update($invoice, [
            'customer_id' => $customer->id,
            'invoice_type' => 'service',
            'invoice_date' => now()->toDateString(),
            'discount' => 0,
            'discount_type' => 'fixed',
            'tax_amount' => 0,
            'items' => [
                ['product_id' => $product->id, 'description' => 'Part', 'quantity' => 2, 'unit_price' => 100000, 'discount' => 0, 'discount_type' => 'fixed'],
                ['product_id' => null, 'description' => 'Jasa servis', 'quantity' => 1, 'unit_price' => 50000, 'discount' => 0, 'discount_type' => 'fixed'],
            ],
        ]);

        $invoice->refresh();
        $this->assertEquals(250000.0, (float) $invoice->grand_total);

        // Journal re-aligned: still exactly one AR and one COGS entry (no dupes),
        // now reflecting the new financials.
        $this->assertEquals(1, $this->countEntries($invoice->id, 'ar_invoice'));
        $this->assertEquals(250000.0, $this->totalEntryLines('ar_invoice', $invoice->id, true));
        $this->assertEquals(250000.0, $this->totalEntryLines('ar_invoice', $invoice->id, false));
        $this->assertEquals(1, $this->countEntries($invoice->id, 'cogs'));
        $this->assertEquals(100000.0, $this->totalEntryLines('cogs', $invoice->id, true));

        // Ledger remains globally balanced.
        $this->assertEquals(
            round((float) JournalEntryLine::sum('debit'), 2),
            round((float) JournalEntryLine::sum('credit'), 2)
        );

        // Stock consumption for the +1 part delta.
        $this->assertEquals(8, (int) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity'));
    }

    public function test_creating_invoice_with_percent_discount_uses_percent_field(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->seedAccounts();
        $customer = Customer::create(['name' => 'Invoice Percent', 'phone' => '0818777777']);

        $invoice = app(InvoiceService::class)->create([
            'customer_id' => $customer->id,
            'invoice_type' => 'service',
            'invoice_date' => now()->toDateString(),
            'discount' => 0,
            'discount_type' => 'percent',
            'discount_percent' => 10,
            'tax_amount' => 0,
            'items' => [
                ['product_id' => null, 'description' => 'Jasa servis', 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0, 'discount_type' => 'fixed'],
            ],
        ]);

        $this->assertEquals('percent', $invoice->discount_type);
        $this->assertEquals(10.0, (float) $invoice->discount_percent);
        $this->assertEquals(10000.0, (float) $invoice->discount);
        $this->assertEquals(90000.0, (float) $invoice->grand_total);
    }

    private function countEntries(int $invoiceId, string $type): int
    {
        return JournalEntry::where('reference_type', Invoice::class)
            ->whereNotNull('reference_id')
            ->where('reference_id', $invoiceId)
            ->where('entry_type', $type)
            ->count();
    }
}
