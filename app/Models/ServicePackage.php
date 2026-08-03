<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'repair_category_id', 'price', 'estimated_hours', 'description', 'items', 'is_active'])]
class ServicePackage extends Model
{
    protected $casts = [
        'price' => 'decimal:2',
        'estimated_hours' => 'decimal:1',
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    public function repairCategory(): BelongsTo
    {
        return $this->belongsTo(RepairCategory::class);
    }
}
