<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['observation_type_id', 'observation_point'])]
class ObservationPoint extends Model
{
    use HasFactory, SoftDeletes;

    public function observationType(): BelongsTo
    {
        return $this->belongsTo(ObservationType::class);
    }

    public function serviceObservationPoints(): HasMany
    {
        return $this->hasMany(ServiceObservationPoint::class);
    }
}
