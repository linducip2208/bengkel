<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with(['productType', 'unit', 'supplier']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($typeId = $request->get('type_id')) {
            $query->where('product_type_id', $typeId);
        }

        if ($request->boolean('low_stock')) {
            $query->whereHas('stockRecord', fn($q) => $q->whereColumn('quantity', '<=', 'minimum_stock'));
        }

        $products = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(ProductResource::collection($products)->response()->getData(true));
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['productType', 'unit', 'supplier']);

        return response()->json(new ProductResource($product));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:products,code',
            'name' => 'required|string|max:255',
            'product_type_id' => 'required|exists:product_types,id',
            'unit_id' => 'required|exists:product_units,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'warranty' => 'nullable|string|max:100',
            'current_stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'rack_location' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $product = Product::create($validated);

        return response()->json(new ProductResource($product), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:100|unique:products,code,' . $product->id,
            'name' => 'sometimes|string|max:255',
            'product_type_id' => 'sometimes|exists:product_types,id',
            'unit_id' => 'sometimes|exists:product_units,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'warranty' => 'nullable|string|max:100',
            'minimum_stock' => 'nullable|integer|min:0',
            'rack_location' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $product->update($validated);

        return response()->json(new ProductResource($product));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function stockAdjust(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:add,subtract,set',
            'notes' => 'nullable|string',
        ]);

        $stockRecord = $product->stockRecord()->firstOrCreate([
            'product_id' => $product->id,
        ], [
            'quantity' => 0,
            'minimum_stock' => 0,
        ]);

        $before = $stockRecord->quantity;

        switch ($validated['type']) {
            case 'add':
                $stockRecord->quantity += $validated['quantity'];
                $quantityChange = $validated['quantity'];
                break;
            case 'subtract':
                $stockRecord->quantity = max(0, $stockRecord->quantity - $validated['quantity']);
                $quantityChange = -$validated['quantity'];
                break;
            case 'set':
                $quantityChange = $validated['quantity'] - $stockRecord->quantity;
                $stockRecord->quantity = $validated['quantity'];
                break;
        }

        $stockRecord->save();

        $product->stockHistories()->create([
            'quantity_change' => $quantityChange,
            'previous_stock' => $before,
            'new_stock' => $stockRecord->quantity,
            'type' => $validated['type'],
            'reason' => $validated['notes'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'product_id' => $product->id,
            'quantity_before' => $before,
            'quantity_after' => $stockRecord->quantity,
            'notes' => $validated['notes'] ?? null,
        ]);
    }
}
