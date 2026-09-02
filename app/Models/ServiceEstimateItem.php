<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_estimate_id', 'product_id', 'item_type', 'description',
    'quantity', 'unit_price', 'discount', 'discount_type',
    'tax_rate', 'tax_amount', 'line_total', 'sort_order',
    'estimate_group_id',
])]
class ServiceEstimateItem extends Model
{
    use HasFactory;

    public const TYPE_PART = 'part';

    public const TYPE_LABOR = 'labor';

    public const TYPE_OTHER = 'other';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:3',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(ServiceEstimate::class, 'service_estimate_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ServiceEstimateGroup::class, 'estimate_group_id');
    }
}
