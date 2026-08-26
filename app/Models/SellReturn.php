<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'return_number', 'sale_id', 'invoice_id', 'customer_id',
    'return_date', 'reason', 'refund_amount', 'status', 'created_by',
])]
class SellReturn extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'refund_amount' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SellReturnItem::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            'completed' => '<span class="badge bg-success">Selesai</span>',
            default => '<span class="badge bg-secondary">'.$this->status.'</span>',
        };
    }
}
