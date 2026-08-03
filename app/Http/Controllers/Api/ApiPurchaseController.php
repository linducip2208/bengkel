<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $purchase = DB::transaction(function () use ($validated) {
            $totalAmount = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);

            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'status' => 'ordered',
            ]);

            foreach ($validated['items'] as $itemData) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                ]);
            }

            return $purchase;
        });

        return response()->json(new PurchaseResource($purchase->load('items')), 201);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $validated = $request->validate([
            'purchase_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:draft,ordered,received,cancelled',
        ]);

        $purchase->update($validated);

        return response()->json(new PurchaseResource($purchase));
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $purchase->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function markReceived(Purchase $purchase): JsonResponse
    {
        if ($purchase->status === 'received') {
            return response()->json(['message' => 'Purchase already received.'], 422);
        }

        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $product = \App\Models\Product::find($item->product_id);
                if ($product) {
                    $product->increment('current_stock', $item->quantity);
                }
            }

            $purchase->update([
                'status' => 'received',
            ]);
        });

        return response()->json(new PurchaseResource($purchase->load('items')));
    }
}
