<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::query();

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        if ($label = $request->get('label')) {
            $query->where('label', $label);
        }

        $expenses = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(ExpenseResource::collection($expenses)->response()->getData(true));
    }

    public function show(Expense $expense): JsonResponse
    {
        return response()->json(new ExpenseResource($expense));
    }

    public function store(Request $request, ExpenseService $expenseService): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $expense = $expenseService->create($validated);

        return response()->json(new ExpenseResource($expense), 201);
    }

    public function update(Request $request, Expense $expense, ExpenseService $expenseService): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:1',
            'expense_date' => 'sometimes|date',
            'label' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $expenseService->update($expense, $validated);

        return response()->json(new ExpenseResource($expense));
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
