<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['observation_type_id', 'point', 'category', 'sort_order', 'is_active'])]
class InspectionPointsLibrary extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inspection_points_library';

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function observationType(): BelongsTo
    {
        return $this->belongsTo(ObservationType::class);
    }
}
