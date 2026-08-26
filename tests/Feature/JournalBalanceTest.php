<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\User;
use App\Services\AutoJournalService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Every journal entry written by any flow must satisfy
 * SUM(debit) == SUM(credit).
 */
class JournalBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);

        $role = Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);
    }

    private function assertEveryEntryBalanced(): void
    {
        $entries = JournalEntry::with('lines')->get();
        $this->assertGreaterThan(0, $entries->count(), 'No journals were posted.');

        foreach ($entries as $entry) {
            $debit = round((float) $entry->lines->sum('debit'), 2);
            $credit = round((float) $entry->lines->sum('credit'), 2);

            $this->assertEqualsWithDelta(
                $debit,
                $credit,
                0.005,
                "Journal {$entry->entry_number} ({$entry->entry_type}) is unbalanced: D {$debit} vs K {$credit}"
            );
            $this->assertNotEquals('', $entry->entry_type, 'Auto entries must carry an entry_type.');
        }
    }

    public function test_service_completion_and_payment_post_balanced_entries(): void
    {
        $customer = Customer::create(['name' => 'Cust Jurnal']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-JRN-'.uniqid(),
            'customer_id' => $customer->id,
            'payment_status' => 0,
            'total_amount' => 150000,
            'grand_total' => 150000,
            'paid_amount' => 0,
            'amount_received' => 0,
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'service',
        ]);

        // 1. Invoice issued → AR / Revenue
        app(AutoJournalService::class)->journalInvoiceIssued($invoice);

        // 2. Payment settles → Cash / AR
        $method = PaymentMethod::create(['payment' => 'Transfer BCA', 'slug' => 'transfer-'.uniqid(), 'is_active' => true]);
        $payment = PaymentRecord::create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 150000,
            'payment_date' => now(),
        ]);
        app(AutoJournalService::class)->journalInvoicePayment($payment);
        $invoice->update(['paid_amount' => 150000, 'payment_status' => 2]);

        // 3. Expense → Expense / Cash
        $expense = Expense::create([
            'label' => 'Beli oli mesin kantor',
            'amount' => 75000,
            'expense_date' => now()->toDateString(),
            'created_by' => auth()->id() ?? 1,
        ]);
        app(AutoJournalService::class)->journalExpense($expense);

        // Idempotency: re-running never duplicates
        app(AutoJournalService::class)->journalInvoiceIssued($invoice);
        app(AutoJournalService::class)->journalExpense($expense);

        $this->assertEquals(
            1,
            JournalEntry::where('reference_type', Invoice::class)->where('reference_id', $invoice->id)->where('entry_type', 'ar_invoice')->count()
        );
        $this->assertEquals(
            1,
            JournalEntry::where('reference_type', Expense::class)->where('reference_id', $expense->id)->where('entry_type', 'expense')->count()
        );

        $this->assertEveryEntryBalanced();

        // Trial-balance style check across all lines:
        $totalDebit = round((float) JournalEntryLine::sum('debit'), 2);
        $totalCredit = round((float) JournalEntryLine::sum('credit'), 2);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.005);
    }

    public function test_unbalanced_manual_entry_is_rejected_by_service_guard(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tidak seimbang');

        $service = app(AutoJournalService::class);
        $method = new \ReflectionMethod($service, 'createEntry');
        $method->setAccessible(true);

        $cash = $service->getDefaultAccount('asset', 'Cash');
        $revenue = $service->getDefaultAccount('income', 'Service Revenue');

        $method->invoke(
            $service,
            'TEST-UNBALANCED-1',
            'manual',
            now(),
            'Uji jurnal timpang',
            Invoice::class,
            [
                [$cash, 500.0, 0.0, 'D'],
                [$revenue, 0.0, 400.0, 'K'],
            ],
            null,
            null,
        );
    }

    public function test_pos_over_tender_does_not_inflate_revenue(): void
    {
        $customer = Customer::create(['name' => 'Cust Kembalian']);
        $cash = ChartOfAccount::create(['code' => '1000'.rand(10, 99), 'name' => 'Cash X'.uniqid(), 'type' => 'asset', 'is_active' => true]);
        $ar = ChartOfAccount::create(['code' => '1100'.rand(10, 99), 'name' => 'AR X'.uniqid(), 'type' => 'asset', 'is_active' => true]);
        $revenue = ChartOfAccount::create(['code' => '4000'.rand(10, 99), 'name' => 'Rev X'.uniqid(), 'type' => 'income', 'is_active' => true]);

        $invoice = Invoice::create([
            'invoice_number' => 'POS-JRN-'.uniqid(),
            'customer_id' => $customer->id,
            'payment_status' => 2,
            'total_amount' => 80000,
            'grand_total' => 80000,
            'paid_amount' => 80000,
            'amount_received' => 100000,
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'pos',
        ]);

        $method = PaymentMethod::create(['payment' => 'Cash '.uniqid(), 'slug' => uniqid(), 'is_active' => true]);
        $payment = PaymentRecord::create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => 100000, // customer handed over 100k for an 80k sale
            'payment_date' => now(),
        ]);

        app(AutoJournalService::class)->journalInvoiceIssued($invoice);
        // Controller passes the effective (net) amount — change is NOT revenue.
        app(AutoJournalService::class)->journalInvoicePayment($payment, min(80000.0, (float) $payment->amount));

        $this->assertEveryEntryBalanced();

        $paymentEntry = JournalEntry::where('reference_type', PaymentRecord::class)
            ->where('reference_id', $payment->id)
            ->first();
        $debitTotal = round((float) $paymentEntry->lines()->sum('debit'), 2);
        $this->assertEquals(80000.0, $debitTotal, 'Cash-in-drawer must equal the net amount, not the tendered cash.');
    }
}
