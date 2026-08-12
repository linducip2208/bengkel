<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    protected $fillable = ['name', 'description', 'is_active', 'selling_price_group_id'];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function sellingPriceGroup(): BelongsTo
    {
        return $this->belongsTo(SellingPriceGroup::class);
    }
}
