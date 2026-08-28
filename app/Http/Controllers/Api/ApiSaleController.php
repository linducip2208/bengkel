<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSaleController extends Controller
{
    public function __construct(private SaleService $saleService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Sale::query()->with(['customer', 'vehicle', 'items']);

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('sale_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('sale_date', '<=', $dateTo);
        }

        $sales = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(SaleResource::collection($sales)->response()->getData(true));
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load(['customer', 'vehicle', 'items.product', 'invoices']);

        return response()->json(new SaleResource($sale));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'sale_date' => 'required|date',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $sale = $this->saleService->create($validated);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new SaleResource($sale->load('items.product', 'customer')), 201);
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'sale_date' => 'sometimes|date',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ]);

        if ($sale->invoices()->exists()) {
            return response()->json(['message' => 'Penjualan sudah memiliki invoice dan tidak dapat diubah. Gunakan koreksi/credit note.'], 422);
        }

        try {
            $sale = $this->saleService->update($sale, $validated);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new SaleResource($sale));
    }

    public function destroy(Sale $sale): JsonResponse
    {
        try {
            $this->saleService->delete($sale);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Deleted successfully']);
    }
}
