<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService extends BaseService
{
    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = auth()->id() ?? 1;
            $expense = Expense::create($data);
            app(AutoJournalService::class)->journalExpense($expense);

            return $expense;
        });
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
