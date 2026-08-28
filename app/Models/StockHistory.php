<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'product_id', 'quantity_change', 'previous_stock', 'new_stock',
    'type', 'reference_type', 'reference_id', 'reason', 'user_id',
])]
class StockHistory extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:2',
            'previous_stock' => 'decimal:2',
            'new_stock' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
