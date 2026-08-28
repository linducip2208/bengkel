<?php

namespace App\Services;

use App\Http\Controllers\Tenant\LoyaltyController;
use App\Models\ActivityLog;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseService
{
    /**
     * Record a payment against an invoice.
     *
     * Concurrency-safe: the invoice row is locked FOR UPDATE before the
     * remaining-balance check and held until commit, so two simultaneous
     * requests cannot both pass the overpayment guard (double-click /
     * retry cannot create duplicate money).
     *
     * Idempotency: when $data['idempotency_key'] is supplied and a payment
     * already exists for that key, the existing record is returned untouched.
     */
    public function process(Invoice $invoice, array $data): PaymentRecord
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;

        if ($idempotencyKey) {
            $existing = PaymentRecord::withoutBranchScope()->where('invoice_id', $invoice->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($invoice, $data, $idempotencyKey) {
            // Re-read under lock to get authoritative paid_amount.
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->first();

            // Guard overpayment — jangan izinkan bayar melebihi sisa tagihan
            $alreadyPaid = round((float) $locked->paid_amount, 2);
            $grandTotal = round((float) $locked->grand_total, 2);
            $remaining = round($grandTotal - $alreadyPaid, 2);

            if ($remaining <= 0) {
                throw new \RuntimeException('Invoice sudah lunas, tidak ada sisa tagihan.');
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new \RuntimeException('Jumlah pembayaran harus lebih besar dari nol.');
            }
            if ($amount > $remaining + 0.009) {
                throw new \RuntimeException('Jumlah pembayaran melebihi sisa tagihan (sisa: '.number_format($remaining, 0, ',', '.').').');
            }

            $data['amount'] = $amount;
            $data['created_by'] = auth()->id() ?? $locked->created_by ?? 1;
            $data['branch_id'] = $locked->branch_id;
            $data['idempotency_key'] = $idempotencyKey;
            try {
                $payment = $locked->paymentRecords()->create($data);
            } catch (UniqueConstraintViolationException $exception) {
                if (! $idempotencyKey) {
                    throw $exception;
                }

                return PaymentRecord::withoutBranchScope()
                    ->where('invoice_id', $locked->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->firstOrFail();
            }

            $wasAlreadyPaid = $locked->payment_status >= 2;
            $newPaid = round($alreadyPaid + $amount, 2);
            $locked->update([
                'paid_amount' => $newPaid,
                'amount_received' => $newPaid,
                'payment_status' => $newPaid >= $grandTotal - 0.009 ? 2 : 1,
            ]);

            // A transfer (fresh cash movement) is booked once against the
            // Kas/Income ledger. One Income row per payment keeps the revenue
            // ledger equal to the sum of actual collected payments, so partial
            // payments are reflected correctly instead of being understated by
            // the unpaid remainder on the first payment.
            Income::create([
                'invoice_number' => $locked->invoice_number,
                'customer_id' => $locked->customer_id,
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $amount,
                'income_date' => $data['payment_date'] ?? now(),
                'label' => 'Pembayaran Invoice '.$locked->invoice_number,
                'created_by' => auth()->id() ?? $locked->created_by ?? 1,
                'branch_id' => $locked->branch_id,
            ]);

            if (! $wasAlreadyPaid && (int) $locked->payment_status === 2) {
                LoyaltyController::earnFromInvoice($locked);
            }

            app(AutoJournalService::class)->journalInvoicePayment($payment);

            ActivityLog::record('payment.create', $payment, "Pembayaran {$amount} untuk invoice {$locked->invoice_number}");

            return $payment;
        });
    }

    public function getTotalCollected($start, $end): float
    {
        return PaymentRecord::whereBetween('created_at', [$start, $end])->sum('amount');
    }
}
