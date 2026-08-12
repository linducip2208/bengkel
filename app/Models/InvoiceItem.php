<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['invoice_id', 'product_id', 'description', 'quantity', 'unit_price', 'total_price', 'serial_number', 'warranty_expiry', 'sold_date', 'discount', 'discount_type'])]
class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'warranty_expiry' => 'date',
            'sold_date' => 'date',
        ];
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_expiry !== null
            && now()->lt($this->warranty_expiry);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
