<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['service_id', 'observation_point_id', 'checked', 'comment'])]
class ServiceObservationPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'checked' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function observationPoint(): BelongsTo
    {
        return $this->belongsTo(ObservationPoint::class);
    }
}
