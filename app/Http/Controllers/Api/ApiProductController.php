<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\StockService;
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
            $query->whereHas('stockRecord', fn ($q) => $q->whereColumn('quantity', '<=', 'minimum_stock'));
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

        // Delegate to ProductService so the initial StockRecord + history are
        // created consistently (previously API-created products had no stock).
        $product = app(ProductService::class)->create($validated);

        return response()->json(new ProductResource($product), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:100|unique:products,code,'.$product->id,
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
            'quantity' => 'required|integer|min:0',
            'type' => 'required|in:add,subtract,set',
            'notes' => 'nullable|string',
        ]);

        try {
            // Route through the locked StockService — previously this wrote a
            // stale property mutation with no transaction or sufficiency check.
            match ($validated['type']) {
                'add' => StockService::increment(
                    $product->id, (int) $validated['quantity'], 'adjustment_add', $validated['notes'] ?? null,
                ),
                'subtract' => StockService::decrement(
                    $product->id, (int) $validated['quantity'], 'adjustment_reduce', $validated['notes'] ?? null,
                ),
                'set' => StockService::set(
                    $product->id, (int) $validated['quantity'], 'opname', $validated['notes'] ?? null,
                ),
            };
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $product->refresh();
        $stock = $product->stockRecord()->withoutGlobalScopes()->first();

        return response()->json([
            'product_id' => $product->id,
            'quantity_after' => (int) ($stock?->quantity ?? 0),
            'notes' => $validated['notes'] ?? null,
        ]);
    }
}
