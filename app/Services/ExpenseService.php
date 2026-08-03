<?php

namespace App\Services;

use App\Models\Expense;

class ExpenseService extends BaseService
{
    public function create(array $data): Expense
    {
        $data['created_by'] = auth()->id() ?? 1;
        return Expense::create($data);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        return $expense;
    }

    public function getTotalBetween($start, $end): float
    {
        return Expense::whereBetween('expense_date', [$start, $end])->sum('amount');
    }
}
