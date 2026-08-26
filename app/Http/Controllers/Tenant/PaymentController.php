<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function store(PaymentRequest $request): RedirectResponse
    {
        $this->authorize('payments.process');

        $invoice = Invoice::findOrFail($request->route('invoice'));
        try {
            $this->paymentService->process($invoice, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function create(Invoice $invoice): View
    {
        $invoice->load(['customer', 'paymentRecords']);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $totalPaid = (float) $invoice->paid_amount;
        $remaining = max($invoice->grand_total - $totalPaid, 0);

        return view('payments.create', compact('invoice', 'paymentMethods', 'remaining'));
    }

    public function history(Invoice $invoice): View
    {
        $invoice->load(['paymentRecords.paymentMethod', 'customer']);

        return view('payments.history', compact('invoice'));
    }
}
