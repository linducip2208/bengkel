<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\WarrantyClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiWarrantyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WarrantyClaim::query()->with(['customer', 'invoiceItem.product', 'invoiceItem.invoice']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $claims = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($claims);
    }

    public function show(WarrantyClaim $warrantyClaim): JsonResponse
    {
        return response()->json($warrantyClaim->load(['customer', 'invoiceItem.product', 'invoiceItem.invoice']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_item_id' => 'required|exists:invoice_items,id',
            'claim_date' => 'required|date',
            'complaint' => 'required|string|max:1000',
        ]);

        $item = InvoiceItem::with('invoice')->findOrFail($validated['invoice_item_id']);

        $claim = WarrantyClaim::create($validated + [
            'customer_id' => $item->invoice->customer_id,
            'status' => 'submitted',
        ]);

        return response()->json($claim->load(['customer', 'invoiceItem.product']), 201);
    }

    public function update(Request $request, WarrantyClaim $warrantyClaim): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:submitted,approved,rejected,resolved',
            'resolution' => 'nullable|string|max:1000',
        ]);

        $warrantyClaim->update($validated);

        return response()->json($warrantyClaim->load(['customer', 'invoiceItem.product']));
    }

    public function destroy(WarrantyClaim $warrantyClaim): JsonResponse
    {
        $warrantyClaim->delete();

        return response()->json(['message' => 'Klaim garansi dihapus.']);
    }
}
