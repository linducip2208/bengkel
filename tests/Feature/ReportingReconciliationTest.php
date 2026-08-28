<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            ['1000', 'Cash', 'asset'],
            ['1100', 'Accounts Receivable', 'asset'],
        ] as [$code, $name, $type]) {
            ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'is_active' => true]);
        }
    }

    private function makePaymentMethod(string $slug = 'cash'): PaymentMethod
    {
        return PaymentMethod::create([
            'payment' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function makeInvoice(float $grandTotal, int $paymentStatus = 0, float $paid = 0.0, ?string $dueDate = null): Invoice
    {
        $customer = Customer::create(['name' => 'Recon '.uniqid()]);

        return Invoice::create([
            'invoice_number' => 'INV-REC-'.uniqid(),
            'customer_id' => $customer->id,
            'payment_status' => $paymentStatus,
            'total_amount' => $grandTotal,
            'grand_total' => $grandTotal,
            'paid_amount' => $paid,
            'amount_received' => $paid,
            'invoice_date' => now()->toDateString(),
            'due_date' => $dueDate,
            'invoice_type' => 'service',
            'created_by' => $this->actingUser()->id,
        ]);
    }

    private function actingUser(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    public function test_invoice_paid_amount_equals_sum_of_payment_records_after_partial_and_full_payment(): void
    {
        $this->actingAs($this->actingUser());
        $method = $this->makePaymentMethod();
        $invoice = $this->makeInvoice(250000);

        $this->assertSame(0.0, round($invoice->paid_amount, 2));
        $this->assertSame(0, $invoice->paymentRecords()->count());

        // Partial payment of 100.000.
        app(PaymentService::class)->process($invoice, [
            'amount' => 100000,
            'payment_method_id' => $method->id,
            'payment_date' => '2026-08-10 09:00:00',
        ]);

        // Full payment of the remaining 150.000.
        app(PaymentService::class)->process($invoice, [
            'amount' => 150000,
            'payment_method_id' => $method->id,
            'payment_date' => '2026-08-15 09:00:00',
        ]);

        $fresh = $invoice->fresh();

        // Invariant 1: paid_amount == Σ PaymentRecord.amount.
        $recordSum = round((float) $fresh->paymentRecords()->sum('amount'), 2);
        $this->assertSame(250000.0, round((float) $fresh->paid_amount, 2));
        $this->assertSame(250000.0, $recordSum);
        $this->assertSame(round((float) $fresh->paid_amount, 2), $recordSum);

        // Invariant 2: Income ledger (cash) == total collected, dated by payment date.
        // By income_date: one Income per payment on its own date.
        $this->assertSame(2, Income::count());
        $this->assertSame(100000.0, round((float) Income::whereDate('income_date', '2026-08-10')->sum('amount'), 2));
        $this->assertSame(150000.0, round((float) Income::whereDate('income_date', '2026-08-15')->sum('amount'), 2));
        $this->assertSame(250000.0, round((float) Income::sum('amount'), 2));

        // Fully paid → payment_status = 2.
        $this->assertSame(2, (int) $fresh->payment_status);
    }

    public function test_ar_aging_remaining_equals_grand_total_minus_paid_and_excludes_paid_invoices(): void
    {
        $this->actingAs($this->actingUser());
        $method = $this->makePaymentMethod();

        // 1) Fully paid invoice → must NOT appear in AR aging (payment_status != 2 filter).
        $paidInvoice = $this->makeInvoice(100000, 2, 100000, now()->subDays(40)->toDateString());

        // 2) Partial paid invoice, current (not overdue) → remaining = 50.000, bucket 'current'.
        $partialInvoice = $this->makeInvoice(150000, 1, 100000, now()->addDays(5)->toDateString());

        // 3) Overdue unpaid invoice (due 45 days ago) → remaining = grand_total, bucket '31-60'.
        $overdueInvoice = $this->makeInvoice(200000, 0, 0, now()->subDays(45)->toDateString());

        $report = app(ReportService::class)->arAgingReport();

        $ids = $report['invoices']->pluck('id');
        $this->assertFalse($ids->contains($paidInvoice->id), 'Paid invoice must be excluded from AR aging.');

        $partial = $report['invoices']->firstWhere('id', $partialInvoice->id);
        $this->assertNotNull($partial);
        $this->assertSame(50000.0, round($partial->remaining, 2));
        $this->assertSame('current', $partial->age_group);

        $overdue = $report['invoices']->firstWhere('id', $overdueInvoice->id);
        $this->assertNotNull($overdue);
        $this->assertSame(200000.0, round($overdue->remaining, 2));
        $this->assertSame('31-60', $overdue->age_group);

        // Aging totals reconcile against remaining (excludes paid invoice).
        $agingTotal = collect($report['aging'])->sum('total');
        $this->assertSame(
            round($report['invoices']->sum('remaining'), 2),
            round($agingTotal, 2)
        );
        $this->assertSame(250000.0, round($agingTotal, 2));
    }

    public function test_financial_report_income_reconciles_with_cash_collected(): void
    {
        $this->actingAs($this->actingUser());
        $method = $this->makePaymentMethod();

        // Cash collected is booked as Income on the payment date, not the invoice date.
        $invoice = $this->makeInvoice(250000);

        // Post a partial payment dated in August.
        app(PaymentService::class)->process($invoice, [
            'amount' => 100000,
            'payment_method_id' => $method->id,
            'payment_date' => '2026-08-10 09:00:00',
        ]);

        $report = app(ReportService::class)->financialReport([
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);

        // total_income is cash collected in the period (by income_date) — equals the payment.
        $this->assertSame(100000.0, round($report['total_income'], 2));

        // The invoice is only partially paid, so it is NOT counted in paid_invoices
        // (which reflects fully-paid invoices only, keyed by invoice_date).
        $this->assertSame(0, (int) $report['paid_count']);
        $this->assertSame(0.0, round($report['paid_invoices'], 2));
    }
}
