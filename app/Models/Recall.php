<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'vehicle_brand_id', 'title', 'description', 'issue_date', 'severity', 'is_active'])]
class Recall extends Model
{
    protected $casts = ['issue_date' => 'date', 'is_active' => 'boolean'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function vehicleBrand(): BelongsTo { return $this->belongsTo(VehicleBrand::class); }
}
