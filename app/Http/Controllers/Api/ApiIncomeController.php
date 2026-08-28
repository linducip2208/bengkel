<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use App\Services\IncomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiIncomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Income::query()->with(['customer', 'paymentMethod']);

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('income_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('income_date', '<=', $dateTo);
        }

        $incomes = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(IncomeResource::collection($incomes)->response()->getData(true));
    }

    public function show(Income $income): JsonResponse
    {
        $income->load(['customer', 'paymentMethod']);

        return response()->json(new IncomeResource($income));
    }

    public function store(Request $request, IncomeService $incomeService): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'income_date' => 'required|date',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $income = $incomeService->create($validated);

        return response()->json(new IncomeResource($income), 201);
    }

    public function update(Request $request, Income $income, IncomeService $incomeService): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:1',
            'income_date' => 'sometimes|date',
            'label' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $incomeService->update($income, $validated);

        return response()->json(new IncomeResource($income));
    }

    public function destroy(Income $income): JsonResponse
    {
        $income->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
