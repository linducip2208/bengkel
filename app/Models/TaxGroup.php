<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_active'])]
class TaxGroup extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function taxGroupRates(): HasMany
    {
        return $this->hasMany(TaxGroupRate::class);
    }

    public function rates(): BelongsToMany
    {
        return $this->belongsToMany(TaxRate::class, 'tax_group_rates');
    }
}
