<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['invoice_number', 'customer_id', 'payment_method_id', 'bank_account_id', 'amount', 'income_date', 'label', 'description', 'created_by', 'branch_id'])]
class Income extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'income_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function incomeHistoryRecords(): HasMany
    {
        return $this->hasMany(IncomeHistoryRecord::class);
    }
}
