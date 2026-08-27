<?php

namespace App\Models;

use App\Models\Concerns\HasRaceSafeUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['type', 'slug', 'description', 'is_active'])]
class ProductType extends Model
{
    use HasFactory, HasRaceSafeUniqueSlug, SoftDeletes;

    protected const UNIQUE_SLUG_SOURCE = 'type';

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (! $model->isDirty('slug') && ($model->isDirty('type') || blank($model->slug))) {
                $model->slug = static::generateUniqueSlug((string) $model->type, $model->id);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->type;
    }
}
