<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->with(['service.customer']);

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        if ($customerId = $request->get('customer_id')) {
            $query->whereHas('service', fn($q) => $q->where('customer_id', $customerId));
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('invoice_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('invoice_date', '<=', $dateTo);
        }

        $invoices = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(InvoiceResource::collection($invoices)->response()->getData(true));
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['service.customer', 'service.technicians']);

        return response()->json(new InvoiceResource($invoice));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'invoice_date' => 'required|date',
            'payment_status' => 'nullable|integer|in:0,1,2',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::create([
            'service_id' => $validated['service_id'],
            'invoice_date' => $validated['invoice_date'],
            'payment_status' => $validated['payment_status'] ?? 0,
            'discount' => $validated['discount'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(new InvoiceResource($invoice->load('service')), 201);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => 'nullable|integer|in:0,1,2',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($validated);

        return response()->json(new InvoiceResource($invoice));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function pdf(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'message' => 'PDF generation endpoint',
            'invoice_id' => $invoice->id,
            'invoice_no' => $invoice->invoice_number,
        ]);
    }
}
