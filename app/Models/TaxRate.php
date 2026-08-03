<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['taxname', 'tax', 'description', 'is_active'])]
class TaxRate extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tax' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function serviceTaxes(): HasMany
    {
        return $this->hasMany(ServiceTax::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->taxname;
    }

    public function getRateAttribute(): ?float
    {
        return $this->tax !== null ? (float) $this->tax : null;
    }
}
