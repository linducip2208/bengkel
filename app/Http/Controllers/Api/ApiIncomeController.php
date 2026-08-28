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
        if ($this->isSystemGenerated($income)) {
            return response()->json(['message' => 'Pendapatan yang berasal dari pembayaran invoice/POS tidak dapat diubah manual. Gunakan koreksi/credit note.'], 422);
        }

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
        if ($this->isSystemGenerated($income)) {
            return response()->json(['message' => 'Pendapatan yang berasal dari pembayaran invoice/POS tidak dapat dihapus manual. Gunakan reversal resmi.'], 422);
        }

        $income->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * Incomes generated automatically by PaymentService / POS always carry an
     * invoice_number linking them to the originating invoice. Manually entered
     * incomes do not, and remain editable/deletable.
     */
    private function isSystemGenerated(Income $income): bool
    {
        return ! empty($income->invoice_number);
    }
}
