<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['category_name'])]
class CheckoutCategory extends Model
{
    use HasFactory, SoftDeletes;

    public function checkoutResults(): HasMany
    {
        return $this->hasMany(CheckoutResult::class);
    }
}
