<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentRecordResource;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiPaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice, PaymentService $paymentService): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $validated['idempotency_key'] ??= $request->header('Idempotency-Key');

        try {
            $payment = $paymentService->process($invoice, $validated);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(new PaymentRecordResource($payment), 201);
    }
}
