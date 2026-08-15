<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use App\Models\Purchase;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class AutoJournalService
{
    public function journalInvoicePayment(PaymentRecord $payment): void
    {
        $invoice = $payment->invoice;
        if (!$invoice) return;

        $existing = JournalEntry::where('reference_type', PaymentRecord::class)
            ->where('reference_id', $payment->id)
            ->exists();
        if ($existing) return;

        DB::transaction(function () use ($payment, $invoice) {
            $cashAccount = $this->getDefaultAccount('asset', 'Cash');
            $bankAccount = $this->getDefaultAccount('asset', 'Bank');
            $revenueAccount = $this->getDefaultAccount('income', 'Service Revenue');

            $debitAccount = $cashAccount;
            $description = 'Pembayaran tunai invoice ' . ($invoice->invoice_number ?? '#'.$invoice->id);

            if ($payment->paymentMethod && stripos($payment->paymentMethod->name ?? '', 'transfer') !== false) {
                $debitAccount = $bankAccount;
                $description = 'Pembayaran transfer invoice ' . ($invoice->invoice_number ?? '#'.$invoice->id);
            }

            $entry = JournalEntry::create([
                'entry_number' => 'PMT-' . now()->format('Ymd') . '-' . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT),
                'entry_date' => $payment->payment_date ?? now(),
                'description' => $description,
                'reference_type' => PaymentRecord::class,
                'reference_id' => $payment->id,
                'created_by' => $payment->created_by ?? auth()->id() ?? 1,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $debitAccount?->id,
                'debit' => $payment->amount,
                'credit' => 0,
                'description' => 'Kas/Bank masuk',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $revenueAccount?->id,
                'debit' => 0,
                'credit' => $payment->amount,
                'description' => 'Pendapatan jasa',
            ]);
        });
    }

    public function journalPurchase(Purchase $purchase): void
    {
        $existing = JournalEntry::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->exists();
        if ($existing) return;

        DB::transaction(function () use ($purchase) {
            $inventoryAccount = $this->getDefaultAccount('asset', 'Inventory');
            $apAccount = $this->getDefaultAccount('liability', 'Accounts Payable');
            $cashAccount = $this->getDefaultAccount('asset', 'Cash');

            $entry = JournalEntry::create([
                'entry_number' => 'PUR-' . now()->format('Ymd') . '-' . str_pad((string) $purchase->id, 4, '0', STR_PAD_LEFT),
                'entry_date' => $purchase->purchase_date ?? now(),
                'description' => 'Pembelian ' . ($purchase->purchase_no ?? '#'.$purchase->id),
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'created_by' => $purchase->created_by ?? auth()->id() ?? 1,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $inventoryAccount?->id,
                'debit' => $purchase->total_amount,
                'credit' => 0,
                'description' => 'Persediaan masuk',
            ]);

            if ($purchase->status === 'received') {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $apAccount?->id,
                    'debit' => 0,
                    'credit' => $purchase->total_amount,
                    'description' => 'Utang dagang',
                ]);
            } else {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $cashAccount?->id,
                    'debit' => 0,
                    'credit' => $purchase->total_amount,
                    'description' => 'Kas keluar',
                ]);
            }
        });
    }

    public function journalExpense(Expense $expense): void
    {
        $existing = JournalEntry::where('reference_type', Expense::class)
            ->where('reference_id', $expense->id)
            ->exists();
        if ($existing) return;

        DB::transaction(function () use ($expense) {
            $expenseAccount = $this->getDefaultAccount('expense', 'General Expense');
            $cashAccount = $this->getDefaultAccount('asset', 'Cash');

            $entry = JournalEntry::create([
                'entry_number' => 'EXP-' . now()->format('Ymd') . '-' . str_pad((string) $expense->id, 4, '0', STR_PAD_LEFT),
                'entry_date' => $expense->expense_date ?? now(),
                'description' => 'Biaya: ' . ($expense->label ?? 'Pengeluaran #'.$expense->id),
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'created_by' => $expense->created_by ?? auth()->id() ?? 1,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $expenseAccount?->id,
                'debit' => $expense->amount,
                'credit' => 0,
                'description' => 'Biaya operasional',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $cashAccount?->id,
                'debit' => 0,
                'credit' => $expense->amount,
                'description' => 'Kas keluar',
            ]);
        });
    }

    public function getDefaultAccount(string $type, string $name): ?ChartOfAccount
    {
        $defaults = [
            'asset' => ['Cash' => '1000', 'Bank' => '1010', 'Accounts Receivable' => '1100', 'Inventory' => '1200'],
            'liability' => ['Accounts Payable' => '2000'],
            'income' => ['Service Revenue' => '4000', 'Parts Revenue' => '4100'],
            'expense' => ['General Expense' => '5000', 'Cost of Goods Sold' => '5100'],
            'equity' => ['Equity' => '3000'],
        ];

        $code = $defaults[$type][$name] ?? null;
        if (!$code) return null;

        $account = ChartOfAccount::where('code', $code)->first();
        if ($account) return $account;

        $account = ChartOfAccount::where('name', 'like', $name . '%')->first();
        if ($account) return $account;

        $account = ChartOfAccount::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'is_active' => true,
        ]);

        return $account;
    }
}
