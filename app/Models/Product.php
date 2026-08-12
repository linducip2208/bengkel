<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_no', 'code', 'barcode', 'name', 'product_type_id', 'unit_id', 'supplier_id',
    'price', 'cost_price', 'warranty', 'description', 'branch_id'
])]
class Product extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockRecord(): HasOne
    {
        return $this->hasOne(StockRecord::class);
    }

    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getCurrentStockAttribute(): int
    {
        return $this->stockRecord?->quantity ?? 0;
    }

    public function getMinimumStockAttribute(): ?int
    {
        return $this->stockRecord?->minimum_stock ?? null;
    }

    public function getRackLocationAttribute(): ?string
    {
        return $this->stockRecord?->rack_location ?? null;
    }

    public function getStockStatusAttribute(): string
    {
        $stock = $this->current_stock;
        if ($stock <= 0) {
            return 'out';
        }
        $min = $this->minimum_stock;
        if ($min && $stock <= $min) {
            return 'low';
        }
        return 'in_stock';
    }

    public function getStockStatusBadgeAttribute(): string
    {
        return match ($this->stock_status) {
            'out' => '<span class="badge bg-danger">Habis</span>',
            'low' => '<span class="badge bg-warning text-dark">Rendah</span>',
            default => '<span class="badge bg-success">Tersedia</span>',
        };
    }

    public function isUnderWarranty(?string $soldDate = null): bool
    {
        $warranty = $this->warranty;

        if (empty($warranty)) {
            return false;
        }

        $referenceDate = $soldDate ?? $this->created_at?->toDateString();

        if (empty($referenceDate)) {
            return false;
        }

        $months = $this->parseWarrantyMonths($warranty);

        if ($months <= 0) {
            return false;
        }

        $expiryDate = \Carbon\Carbon::parse($referenceDate)->addMonths($months);

        return now()->lte($expiryDate);
    }

    public function getWarrantyExpiryDate(?string $soldDate = null): ?string
    {
        $warranty = $this->warranty;

        if (empty($warranty)) {
            return null;
        }

        $referenceDate = $soldDate ?? $this->created_at?->toDateString();

        if (empty($referenceDate)) {
            return null;
        }

        $months = $this->parseWarrantyMonths($warranty);

        if ($months <= 0) {
            return null;
        }

        return \Carbon\Carbon::parse($referenceDate)->addMonths($months)->format('d/m/Y');
    }

    protected function parseWarrantyMonths(string $warranty): int
    {
        $warranty = mb_strtolower(trim($warranty));

        if (preg_match('/(\d+)\s*(?:bulan|month)/', $warranty, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/(\d+)\s*(?:tahun|year)/', $warranty, $m)) {
            return (int) $m[1] * 12;
        }

        if (is_numeric($warranty)) {
            return (int) $warranty;
        }

        return 0;
    }
}
