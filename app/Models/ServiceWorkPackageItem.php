<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_work_package_id
 * @property string $item_type
 * @property int|null $product_id
 * @property string $description
 * @property float $quantity
 * @property float $unit_price
 * @property int $standard_minutes
 * @property float $line_total
 */
#[Fillable(['service_work_package_id', 'item_type', 'product_id', 'description', 'quantity', 'unit_price', 'standard_minutes', 'line_total', 'sort_order'])]
class ServiceWorkPackageItem extends Model
{
    public const TYPE_LABOR = 'labor';

    public const TYPE_PART = 'part';

    public const TYPE_OTHER = 'other';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServiceWorkPackage::class, 'service_work_package_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
