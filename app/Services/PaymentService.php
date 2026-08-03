<?php

namespace App\Services;

use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use Illuminate\Http\Request;

class PaymentService extends BaseService
{
    public function process(Invoice $invoice, array $data): PaymentRecord
    {
        $data['created_by'] = auth()->id() ?? 1;
        $payment = $invoice->paymentRecords()->create($data);

        $newPaid = $invoice->paid_amount + $data['amount'];
        $invoice->update([
            'paid_amount' => $newPaid,
            'amount_received' => $newPaid,
            'payment_status' => $newPaid >= $invoice->grand_total ? 2 : ($newPaid > 0 ? 1 : 0),
        ]);

        if ($invoice->payment_status === 2) {
            Income::create([
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer_id,
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $invoice->grand_total,
                'income_date' => now(),
                'label' => 'Pembayaran Invoice ' . $invoice->invoice_number,
                'created_by' => auth()->id() ?? 1,
            ]);
        }

        return $payment;
    }

    public function getTotalCollected($start, $end): float
    {
        return PaymentRecord::whereBetween('created_at', [$start, $end])->sum('amount');
    }

    public function store(Request $request) { abort(501); }
}
