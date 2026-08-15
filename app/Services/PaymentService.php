<?php

namespace App\Services;

use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use App\Services\AutoJournalService;
use Illuminate\Http\Request;

class PaymentService extends BaseService
{
    public function process(Invoice $invoice, array $data): PaymentRecord
    {
        // Guard overpayment — jangan izinkan bayar melebihi sisa tagihan
        $alreadyPaid = (float) $invoice->paid_amount;
        $remaining = (float) $invoice->grand_total - $alreadyPaid;
        if ($remaining <= 0) {
            throw new \RuntimeException('Invoice sudah lunas, tidak ada sisa tagihan.');
        }
        if ((float) $data['amount'] > $remaining) {
            throw new \RuntimeException('Jumlah pembayaran melebihi sisa tagihan (sisa: ' . number_format($remaining, 0, ',', '.') . ').');
        }

        $data['created_by'] = auth()->id() ?? 1;
        $payment = $invoice->paymentRecords()->create($data);

        $wasAlreadyPaid = $invoice->payment_status >= 2;
        $newPaid = $invoice->paid_amount + $data['amount'];
        $invoice->update([
            'paid_amount' => $newPaid,
            'amount_received' => $newPaid,
            'payment_status' => $newPaid >= $invoice->grand_total ? 2 : ($newPaid > 0 ? 1 : 0),
        ]);

        if (!$wasAlreadyPaid && $invoice->payment_status === 2) {
            Income::create([
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer_id,
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $invoice->grand_total,
                'income_date' => now(),
                'label' => 'Pembayaran Invoice ' . $invoice->invoice_number,
                'created_by' => auth()->id() ?? 1,
            ]);

            \App\Http\Controllers\Tenant\LoyaltyController::earnFromInvoice($invoice);
            app(AutoJournalService::class)->journalInvoicePayment($payment);
        }

        return $payment;
    }

    public function getTotalCollected($start, $end): float
    {
        return PaymentRecord::whereBetween('created_at', [$start, $end])->sum('amount');
    }
}
