<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['branch_id', 'date', 'type', 'amount', 'description', 'reference', 'created_by'])]
class PettyCash extends Model
{
    use HasBranchScope;

    protected $table = 'petty_cash_transactions';

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
