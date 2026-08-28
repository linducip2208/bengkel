<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseHistoryRecord;
use App\Models\SellReturn;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Automatic accounting integration.
 *
 * Every posted journal satisfies SUM(debit) == SUM(credit) — enforced by
 * createEntry(), which refuses to persist an unbalanced entry.
 *
 * Entries are idempotent per (reference, entry_type): re-running an event
 * never duplicates journals. entry_number is derived from the parent record
 * ID and backed by a UNIQUE index on journal_entries.entry_number.
 */
class AutoJournalService
{
    public function journalInvoiceIssued(Invoice $invoice): void
    {
        if ($this->entryExists($invoice, 'ar_invoice')) {
            return;
        }

        // Split revenue between labor (no product) and parts (product) lines.
        $partsRevenue = 0.0;
        $laborRevenue = 0.0;

        foreach ($invoice->items as $item) {
            if (! empty($item->product_id)) {
                $partsRevenue += (float) $item->total_price;
            } else {
                $laborRevenue += (float) $item->total_price;
            }
        }

        // Discount/tax adjustments reduce the receivable proportionally so
        // the entry always balances against grand_total.
        $gross = round($partsRevenue + $laborRevenue, 2);
        $grandTotal = round((float) $invoice->grand_total, 2);
        if ($gross > 0 && abs($gross - $grandTotal) >= 0.01) {
            $factor = $grandTotal / $gross;
            $partsRevenue = round($partsRevenue * $factor, 2);
            $laborRevenue = round($grandTotal - $partsRevenue, 2);
        }

        $lines = [
            [$this->getDefaultAccount('asset', 'Accounts Receivable'), $grandTotal, 0.0, 'Piutang usaha — Invoice '.($invoice->invoice_number ?? '#'.$invoice->id)],
        ];
        if ($partsRevenue > 0) {
            $lines[] = [$this->getDefaultAccount('income', 'Parts Revenue'), 0.0, $partsRevenue, 'Pendapatan penjualan sparepart'];
        }
        if ($laborRevenue > 0) {
            $lines[] = [$this->getDefaultAccount('income', 'Service Revenue'), 0.0, $laborRevenue, 'Pendapatan jasa servis'];
        }
        // Invoices with a grand total but no item breakdown (manual/legacy
        // rows) must still post a balanced entry.
        if ($partsRevenue <= 0 && $laborRevenue <= 0 && $grandTotal > 0) {
            $lines[] = [$this->getDefaultAccount('income', 'Service Revenue'), 0.0, $grandTotal, 'Pendapatan (tanpa rincian item)'];
        }

        $this->createEntry(
            'INV-'.$invoice->id,
            'ar_invoice',
            $invoice->invoice_date ?? now(),
            'Penerbitan invoice '.($invoice->invoice_number ?? '#'.$invoice->id),
            $invoice,
            $lines,
            $invoice->created_by ?? auth()->id() ?? 1,
        );

        $this->journalCogs($invoice);
    }

    /** Reverse an unpaid issued invoice (void/cancel). */
    public function reverseJournalInvoiceIssued(Invoice $invoice): void
    {
        $original = JournalEntry::where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->where('entry_type', 'ar_invoice')
            ->first();

        if (! $original || $this->entryExists($invoice, 'ar_invoice_reversal')) {
            return;
        }

        $lines = [];
        foreach ($original->lines as $line) {
            $lines[] = [
                $line->account,
                (float) $line->credit,
                (float) $line->debit,
                'Void '.$original->description,
            ];
        }

        $this->createEntry(
            'INVR-'.$invoice->id,
            'ar_invoice_reversal',
            now(),
            'Pembatalan invoice '.($invoice->invoice_number ?? '#'.$invoice->id),
            $invoice,
            $lines,
            auth()->id() ?? 1,
        );
    }

    /**
     * Accrued COGS for parts sold on an invoice: Dr COGS / Cr Inventory
     * at the product's cost price.
     */
    public function journalCogs(Invoice $invoice): void
    {
        if ($this->entryExists($invoice, 'cogs')) {
            return;
        }

        $cogs = 0.0;
        $items = InvoiceItem::withoutGlobalScopes()
            ->where('invoice_id', $invoice->id)
            ->whereNotNull('product_id')
            ->with(['product' => fn ($q) => $q->withoutGlobalScopes()])
            ->get();

        foreach ($items as $item) {
            $cogs += (float) ($item->product?->cost_price ?? 0) * (float) $item->quantity;
        }
        $cogs = round($cogs, 2);

        if ($cogs <= 0) {
            return;
        }

        $this->createEntry(
            'COGS-'.$invoice->id,
            'cogs',
            $invoice->invoice_date ?? now(),
            'HPP sparepart invoice '.($invoice->invoice_number ?? '#'.$invoice->id),
            $invoice,
            [
                [$this->getDefaultAccount('expense', 'Cost of Goods Sold'), $cogs, 0.0, 'HPP persediaan terjual'],
                [$this->getDefaultAccount('asset', 'Inventory'), 0.0, $cogs, 'Persediaan keluar'],
            ],
            $invoice->created_by ?? auth()->id() ?? 1,
        );
    }

    public function journalInvoicePayment(PaymentRecord $payment, ?float $effectiveAmount = null): void
    {
        $invoice = $payment->invoice;
        if (! $invoice) {
            return;
        }

        if ($this->entryExists($payment, 'payment')) {
            return;
        }

        // In POS the PaymentRecord may include cash change handed back to the
        // customer — book only what actually stayed in the drawer.
        $amount = round($effectiveAmount ?? (float) $payment->amount, 2);

        $debitAccount = $this->getDefaultAccount('asset', 'Cash');
        $description = 'Pembayaran tunai invoice '.($invoice->invoice_number ?? '#'.$invoice->id);

        if ($payment->paymentMethod && stripos($payment->paymentMethod->name ?? '', 'transfer') !== false) {
            $debitAccount = $this->getDefaultAccount('asset', 'Bank');
            $description = 'Pembayaran transfer invoice '.($invoice->invoice_number ?? '#'.$invoice->id);
        }

        $this->createEntry(
            'PMT-'.$payment->id,
            'payment',
            $payment->payment_date ?? now(),
            $description,
            $payment,
            [
                [$debitAccount, $amount, 0.0, 'Kas/Bank masuk'],
                [$this->getDefaultAccount('asset', 'Accounts Receivable'), 0.0, $amount, 'Pelunasan piutang'],
            ],
            $payment->created_by ?? auth()->id() ?? 1,
        );
    }

    public function journalPurchase(Purchase $purchase): void
    {
        if ($this->entryExists($purchase, 'purchase')) {
            return;
        }

        $apAccount = $this->getDefaultAccount('liability', 'Accounts Payable');
        $isReceived = $purchase->status === 'received';

        $lines = [
            [$this->getDefaultAccount('asset', 'Inventory'), (float) $purchase->total_amount, 0.0, 'Persediaan masuk'],
        ];
        $lines[] = $isReceived
            ? [$apAccount, 0.0, (float) $purchase->total_amount, 'Utang dagang']
            : [$this->getDefaultAccount('asset', 'Cash'), 0.0, (float) $purchase->total_amount, 'Kas keluar'];

        $this->createEntry(
            'PUR-'.$purchase->id,
            'purchase',
            $purchase->purchase_date ?? now(),
            'Pembelian '.($purchase->purchase_no ?? '#'.$purchase->id),
            $purchase,
            $lines,
            $purchase->created_by ?? auth()->id() ?? 1,
        );
    }

    /** Reverse inventory/AP of a purchase that was returned to the supplier. */
    public function journalPurchaseReturn(Purchase $purchase, float $amount, ?PurchaseHistoryRecord $returnEvent = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->createEntry(
            'PURR-'.$purchase->id.'-'.($returnEvent !== null ? $returnEvent->id : 1),
            'purchase_return',
            now(),
            'Retur pembelian '.($purchase->purchase_no ?? '#'.$purchase->id),
            $returnEvent ?? $purchase,
            [
                [$this->getDefaultAccount('liability', 'Accounts Payable'), $amount, 0.0, 'Pengurangan utang dagang'],
                [$this->getDefaultAccount('asset', 'Inventory'), 0.0, $amount, 'Persediaan retur ke supplier'],
            ],
            auth()->id() ?? 1,
        );
    }

    /** Customer sell-return: reverse revenue + COGS against cash/inventory. */
    public function journalSellReturn(SellReturn $return, float $refundAmount, float $costAmount): void
    {
        if ($this->entryExists($return, 'sell_return')) {
            return;
        }

        $refundAmount = round(max($refundAmount, 0), 2);
        $costAmount = round(max($costAmount, 0), 2);
        if ($refundAmount <= 0 && $costAmount <= 0) {
            return;
        }

        $lines = [];
        if ($refundAmount > 0) {
            $lines[] = [$this->getDefaultAccount('income', 'Parts Revenue'), $refundAmount, 0.0, 'Retur penjualan '.($return->return_number ?? '#'.$return->id)];
            $lines[] = [$this->getDefaultAccount('asset', 'Cash'), 0.0, $refundAmount, 'Pengembalian dana customer'];
        }
        if ($costAmount > 0) {
            $lines[] = [$this->getDefaultAccount('asset', 'Inventory'), $costAmount, 0.0, 'Persediaan kembali'];
            $lines[] = [$this->getDefaultAccount('expense', 'Cost of Goods Sold'), 0.0, $costAmount, 'Reversal HPP retur'];
        }

        $this->createEntry(
            'RET-'.$return->id,
            'sell_return',
            $return->return_date ?? now(),
            'Retur penjualan '.($return->return_number ?? '#'.$return->id),
            $return,
            $lines,
            $return->created_by ?? auth()->id() ?? 1,
        );
    }

    /**
     * Stock adjustment/opname valuation impact: shrinkage is expensed through
     * COGS; surplus credits COGS (or income when no prior cost basis).
     */
    public function journalStockAdjustment(int $productId, float $delta, float $unitCost, string $reason, string $referenceType, int $referenceId): void
    {
        $value = round(abs($delta) * max($unitCost, 0), 2);
        if ($value <= 0) {
            return;
        }

        $dedupeKey = substr(md5("{$referenceType}:{$referenceId}:{$productId}"), 0, 12);
        if (JournalEntry::where('reference_type', $referenceType)->where('reference_id', $referenceId)
            ->where('entry_type', 'stock_adjustment')->where('description', 'like', "%#{$dedupeKey}%")->exists()) {
            return;
        }

        $shrinkage = $delta < 0;
        $lines = [
            $shrinkage
                ? [$this->getDefaultAccount('expense', 'Cost of Goods Sold'), $value, 0.0, 'Penyesuaian stok (selisih kurang)']
                : [$this->getDefaultAccount('asset', 'Inventory'), $value, 0.0, 'Penyesuaian stok (selisih lebih)'],
            $shrinkage
                ? [$this->getDefaultAccount('asset', 'Inventory'), 0.0, $value, 'Persediaan berkurang']
                : [$this->getDefaultAccount('expense', 'Cost of Goods Sold'), 0.0, $value, 'Persediaan bertambah'],
        ];

        $this->createEntry(
            'ADJ-'.$dedupeKey.'-'.$referenceId,
            'stock_adjustment',
            now(),
            'Penyesuaian stok #'.$dedupeKey.': '.$reason,
            $referenceType,
            $lines,
            auth()->id() ?? 1,
            $referenceId,
        );
    }

    public function journalExpense(Expense $expense): void
    {
        if ($this->entryExists($expense, 'expense')) {
            return;
        }

        $this->createEntry(
            'EXP-'.$expense->id,
            'expense',
            $expense->expense_date ?? now(),
            'Biaya: '.($expense->label ?? 'Pengeluaran #'.$expense->id),
            $expense,
            [
                [$this->getDefaultAccount('expense', 'General Expense'), (float) $expense->amount, 0.0, 'Biaya operasional'],
                [$this->getDefaultAccount('asset', 'Cash'), 0.0, (float) $expense->amount, 'Kas keluar'],
            ],
            $expense->created_by ?? auth()->id() ?? 1,
        );
    }

    // --------------------------------------------------------------------
    // Internals
    // --------------------------------------------------------------------

    /**
     * Persist a balanced journal entry. Refuses (throws) when the lines do
     * not balance — an unbalanced journal must never be written silently.
     *
     * @param  array<int, array{0:?ChartOfAccount,1:float,2:float,3:string}>  $lines  [account, debit, credit, description]
     */
    protected function createEntry(
        string $entryNumber,
        string $entryType,
        mixed $entryDate,
        string $description,
        object|string $reference,
        array $lines,
        ?int $userId = null,
        ?int $forcedReferenceId = null,
    ): JournalEntry {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as [$account, $debit, $credit]) {
            if ($account === null) {
                throw new \RuntimeException(
                    "Konfigurasi akun jurnal belum lengkap: {$description}. Lengkapi Chart of Accounts sebelum posting."
                );
            }
            $totalDebit += (float) $debit;
            $totalCredit += (float) $credit;
        }

        if (abs(round($totalDebit, 2) - round($totalCredit, 2)) >= 0.01) {
            throw new \RuntimeException(
                "Jurnal tidak seimbang (D {$totalDebit} vs K {$totalCredit}): {$description}"
            );
        }

        return DB::transaction(function () use ($entryNumber, $entryType, $entryDate, $description, $reference, $lines, $userId, $forcedReferenceId) {
            $refType = is_object($reference) ? $reference::class : (string) $reference;
            $refId = is_object($reference) ? $reference->id : $forcedReferenceId;

            $existing = JournalEntry::where('reference_type', $refType)
                ->when($refId !== null, fn ($q) => $q->where('reference_id', $refId))
                ->where('entry_type', $entryType)
                ->exists();
            if ($existing) {
                return JournalEntry::where('reference_type', $refType)
                    ->when($refId !== null, fn ($q) => $q->where('reference_id', $refId))
                    ->where('entry_type', $entryType)
                    ->first();
            }

            try {
                $entry = JournalEntry::create([
                    'entry_number' => $entryNumber,
                    'entry_type' => $entryType,
                    'entry_date' => $entryDate ?? now(),
                    'description' => $description,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'created_by' => $userId ?? auth()->id() ?? 1,
                ]);
            } catch (UniqueConstraintViolationException) {
                return JournalEntry::where('reference_type', $refType)
                    ->where('reference_id', $refId)
                    ->where('entry_type', $entryType)
                    ->firstOrFail();
            }

            foreach ($lines as [$account, $debit, $credit, $lineDescription]) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $account?->id,
                    'debit' => round((float) $debit, 2),
                    'credit' => round((float) $credit, 2),
                    'description' => $lineDescription ?? $description,
                ]);
            }

            return $entry;
        });
    }

    protected function entryExists(object $reference, string $entryType): bool
    {
        return JournalEntry::where('reference_type', $reference::class)
            ->where('reference_id', $reference->id)
            ->where('entry_type', $entryType)
            ->exists();
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
        if (! $code) {
            return null;
        }

        $account = ChartOfAccount::where('code', $code)->first();
        if ($account) {
            return $account;
        }

        $account = ChartOfAccount::where('name', 'like', $name.'%')->first();
        if ($account) {
            return $account;
        }

        $account = ChartOfAccount::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'is_active' => true,
        ]);

        return $account;
    }
}
