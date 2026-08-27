<?php

namespace App\Models;

use App\Models\Concerns\HasRaceSafeUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['fuel_type', 'slug', 'description', 'is_active'])]
class FuelType extends Model
{
    use HasFactory, HasRaceSafeUniqueSlug, SoftDeletes;

    protected const UNIQUE_SLUG_SOURCE = 'fuel_type';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->fuel_type;
    }
}
