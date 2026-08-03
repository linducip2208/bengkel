<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['expense_date', 'amount', 'label', 'description', 'created_by', 'branch_id'])]
class Expense extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function expenseHistoryRecords(): HasMany
    {
        return $this->hasMany(ExpenseHistoryRecord::class);
    }
}
