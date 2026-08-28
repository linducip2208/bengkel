<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderReceiptService;
use App\Services\PurchaseOrderWorkflowService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiPurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Purchase::query()->with(['supplier']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->get('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $purchases = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(PurchaseResource::collection($purchases)->response()->getData(true));
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load(['supplier', 'items.product']);

        return response()->json(new PurchaseResource($purchase));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $purchase = app(PurchaseService::class)->create($validated);

        return response()->json(new PurchaseResource($purchase->load('items')), 201);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $validated = $request->validate([
            'purchase_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        if ($purchase->status !== 'draft') {
            return response()->json(['message' => 'Hanya purchase Draft yang dapat diubah.'], 422);
        }

        $purchase->update($validated);

        return response()->json(new PurchaseResource($purchase));
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        if ($purchase->status !== 'draft') {
            return response()->json(['message' => 'Hanya purchase Draft yang dapat dihapus. Gunakan reversal untuk transaksi yang sudah diproses.'], 422);
        }
        $purchase->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function markReceived(Purchase $purchase): JsonResponse
    {
        try {
            $purchase = app(PurchaseService::class)->markReceived($purchase);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new PurchaseResource($purchase->fresh('items')));
    }

    public function receivePurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (! auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'inventory'])) {
            return response()->json(['message' => 'Anda tidak berhak menerima purchase order.'], 403);
        }
        $validated = $request->validate([
            'receipt_items' => ['nullable', 'array'],
            'receipt_items.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'receipt_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);
        try {
            $purchase = app(PurchaseOrderReceiptService::class)->receive($purchaseOrder, $validated['receipt_items'] ?? []);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new PurchaseResource($purchase), 201);
    }

    public function transitionPurchaseOrder(PurchaseOrder $purchaseOrder, string $action): JsonResponse
    {
        $roles = $action === 'submit'
            ? ['super_admin', 'admin', 'manager', 'inventory']
            : ['super_admin', 'admin', 'manager'];
        if (! auth()->user()?->hasAnyRole($roles)) {
            return response()->json(['message' => 'Anda tidak berhak mengubah status purchase order.'], 403);
        }
        try {
            $updated = app(PurchaseOrderWorkflowService::class)->transition($purchaseOrder, $action);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['id' => $updated->id, 'status' => $updated->status]);
    }
}
