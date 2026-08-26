<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountingTest extends TestCase
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

    private function makeUser(string $role = 'manager'): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_invoice_payment_creates_balanced_journal_entry_referencing_payment_record(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager);

        $cash = ChartOfAccount::create(['code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $receivable = ChartOfAccount::create(['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => true]);

        $customer = Customer::create(['name' => 'Pelanggan Akuntansi']);

        $paymentMethod = PaymentMethod::create([
            'payment' => 'Cash',
            'slug' => 'cash',
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-ACC-001',
            'customer_id' => $customer->id,
            'payment_method_id' => null,
            'payment_status' => 0,
            'total_amount' => 250000,
            'grand_total' => 250000,
            'paid_amount' => 0,
            'amount_received' => 0,
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'service',
            'created_by' => $manager->id,
        ]);

        $response = $this->post("/invoices/{$invoice->id}/payments", [
            'amount' => 250000,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect();

        $invoice = Invoice::withoutGlobalScopes()->find($invoice->id);
        $this->assertEquals(2, (int) $invoice->payment_status);

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(250000, (float) $payment->amount);

        // Journal entry references the PaymentRecord
        $entry = JournalEntry::where('reference_type', PaymentRecord::class)
            ->where('reference_id', $payment->id)
            ->first();
        $this->assertNotNull($entry);

        $lines = JournalEntryLine::where('journal_entry_id', $entry->id)->get();
        $this->assertCount(2, $lines);

        $totalDebit = (float) $lines->sum('debit');
        $totalCredit = (float) $lines->sum('credit');

        $this->assertEquals((float) $payment->amount, $totalDebit);
        $this->assertEquals($totalDebit, $totalCredit);

        // Accrual: Dr Cash / Cr Accounts Receivable (settlement of piutang).
        $debitLine = $lines->firstWhere('debit', '>', 0);
        $creditLine = $lines->firstWhere('credit', '>', 0);

        $this->assertNotNull($debitLine);
        $this->assertNotNull($creditLine);
        $this->assertEquals($cash->id, $debitLine->chart_of_account_id);
        $this->assertEquals($receivable->id, $creditLine->chart_of_account_id);
    }

    public function test_manual_journal_cannot_be_unbalanced(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $cash = ChartOfAccount::create(['code' => '1001', 'name' => 'Cash 2', 'type' => 'asset', 'is_active' => true]);
        $revenue = ChartOfAccount::create(['code' => '4001', 'name' => 'Other Revenue', 'type' => 'income', 'is_active' => true]);

        $response = $this->post(route('finance.journal.store'), [
            'entry_date' => now()->toDateString(),
            'description' => 'Jurnal tidak seimbang',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 90000],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, JournalEntry::count());
    }
}
