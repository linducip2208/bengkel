<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'period_year', 'period_month', 'base_salary',
    'commission_total', 'allowance', 'deduction', 'net_salary',
    'days_present', 'days_absent', 'status', 'paid_at', 'notes',
])]
class Salary extends Model
{
    protected $casts = [
        'base_salary' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'allowance' => 'decimal:2',
        'deduction' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
