<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 'user_id', 'opened_at', 'closed_at',
    'opening_balance', 'closing_balance', 'expected_balance', 'difference',
    'status', 'notes',
])]
class PosSession extends Model
{
    use HasBranchScope, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'expected_balance' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'pos_session_id');
    }

    public function cashDenominations(): HasMany
    {
        return $this->hasMany(CashDenomination::class);
    }

    public function getRevenueAttribute(): float
    {
        return (float) $this->invoices()->withoutGlobalScopes()->sum('grand_total');
    }

    public function getTransactionCountAttribute(): int
    {
        return $this->invoices()->withoutGlobalScopes()->count();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
