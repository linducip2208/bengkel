<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\InvoiceService;
use App\Services\SettingsService;
use App\Services\WorkshopInvoiceGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->with(['service.customer']);

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        if ($customerId = $request->get('customer_id')) {
            $query->whereHas('service', fn ($q) => $q->where('customer_id', $customerId));
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
            'service_id' => ['required', 'exists:services,id'],
            'invoice_date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // payment_status is ALWAYS system-determined. A newly created invoice
        // is unpaid (0) until PaymentService records an actual payment. A
        // client-supplied payment_status is ignored/never accepted.
        $service = Service::query()->findOrFail($validated['service_id']);

        if (app(WorkshopInvoiceGuard::class)->isModernWorkshopService($service)) {
            return response()->json([
                'message' => 'Invoice Service modern harus dibuat melalui konversi Estimasi setelah pekerjaan dan QC selesai.',
            ], 422);
        }

        if ($invoice = Invoice::where('service_id', $service->id)->first()) {
            return response()->json(new InvoiceResource($invoice->load('service.customer')), 200);
        }

        $totalAmount = round((float) ($service->charge ?? 0), 2);
        $discount = round((float) ($validated['discount'] ?? 0), 2);

        $invoice = Invoice::create([
            'invoice_number' => app(InvoiceService::class)->generateInvoiceNumber(),
            'customer_id' => $service->customer_id,
            'service_id' => $service->id,
            'vehicle_id' => $service->vehicle_id,
            'invoice_date' => $validated['invoice_date'],
            'payment_status' => 0,
            'paid_amount' => 0,
            'amount_received' => 0,
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'grand_total' => max(round($totalAmount - $discount, 2), 0),
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
            'branch_id' => $service->branch_id,
        ]);

        return response()->json(new InvoiceResource($invoice->load('service.customer')), 201);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        // payment_status / paid_amount are owned by PaymentService — they can
        // never be supplied by the client. Only non-financial metadata may be
        // edited here; financial correction requires reversal/credit note flow.
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        if ($request->has('payment_status')) {
            return response()->json(['message' => 'payment_status ditentukan sistem, tidak bisa diubah manual.'], 422);
        }

        // Locked once paid (partial or full): financial fields cannot change.
        if ((float) $invoice->paid_amount > 0 || $invoice->paymentRecords()->exists()) {
            if ($request->hasAny(['customer_id', 'items', 'quantity', 'price', 'discount', 'tax', 'total_amount', 'grand_total', 'service_id'])) {
                return response()->json(['message' => 'Invoice yang sudah dibayar (parsial/lunas) tidak dapat mengubah data finansial. Gunakan credit note/koreksi.'], 422);
            }
        }

        $invoice->update($validated);

        return response()->json(new InvoiceResource($invoice));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->paymentRecords()->exists()) {
            return response()->json(['message' => 'Invoice sudah dibayar dan tidak bisa dihapus.'], 403);
        }

        app(InvoiceService::class)->deleteWithStockRestore($invoice);

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function pdf(Invoice $invoice): Response
    {
        $invoice->load(['items', 'customer', 'paymentRecords.paymentMethod']);
        $totalPaid = round((float) $invoice->paid_amount, 2);
        $remaining = max(round((float) $invoice->grand_total - $totalPaid, 2), 0);
        $settings = app(SettingsService::class)->getCompanyInfo();

        $view = view()->exists('invoices.pdf') ? 'invoices.pdf' : 'invoices.pdf-modern';

        $pdf = Pdf::loadView($view, compact('invoice', 'totalPaid', 'remaining', 'settings'));
        $pdf->setPaper('a4');

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }
}
