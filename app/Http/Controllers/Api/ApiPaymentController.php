<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentRecordResource;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiPaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $payment = PaymentRecord::create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $validated['payment_method_id'],
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(new PaymentRecordResource($payment), 201);
    }
}
