<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'supplier_id', 'price', 'source_url', 'price_date', 'is_active'])]
class SupplierPrice extends Model
{
    protected $table = 'supplier_prices';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
}
