<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::query()->with(['customer', 'vehicle']);

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
        $sale->load(['customer', 'vehicle']);

        return response()->json(new SaleResource($sale));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'sale_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $sale = Sale::create([
            'customer_id' => $validated['customer_id'],
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'sale_date' => $validated['sale_date'],
            'total_amount' => $validated['total_amount'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(new SaleResource($sale), 201);
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'sale_date' => 'sometimes|date',
            'total_amount' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $sale->update($validated);

        return response()->json(new SaleResource($sale));
    }

    public function destroy(Sale $sale): JsonResponse
    {
        $sale->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
