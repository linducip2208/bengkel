<?php

namespace App\Services;

use App\Models\Income;

class IncomeService extends BaseService
{
    public function create(array $data): Income
    {
        $data['created_by'] = auth()->id() ?? 1;
        return Income::create($data);
    }

    public function update(Income $income, array $data): Income
    {
        $income->update($data);
        return $income;
    }

    public function getTotalBetween($start, $end): float
    {
        return Income::whereBetween('income_date', [$start, $end])->sum('amount');
    }
}
