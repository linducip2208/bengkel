<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'service_id', 'user_id', 'role',
    'commission_pct', 'commission_amt', 'paid_at', 'paid_by', 'notes',
])]
class ServiceTechnician extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'commission_pct' => 'decimal:2',
            'commission_amt' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereNull('paid_at');
    }

    public function scopePaid($query)
    {
        return $query->whereNotNull('paid_at');
    }
}
