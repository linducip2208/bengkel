<?php

namespace App\Models;

use App\Models\Concerns\HasRaceSafeUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['repair_category_name', 'slug', 'description', 'is_active'])]
class RepairCategory extends Model
{
    use HasFactory, HasRaceSafeUniqueSlug, SoftDeletes;

    protected const UNIQUE_SLUG_SOURCE = 'repair_category_name';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function observationPoints(): HasMany
    {
        return $this->hasMany(ObservationPoint::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->repair_category_name;
    }
}
