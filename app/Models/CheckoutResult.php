<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['service_id', 'checkout_category_id', 'result', 'comment'])]
class CheckoutResult extends Model
{
    use HasFactory, SoftDeletes;

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function checkoutCategory(): BelongsTo
    {
        return $this->belongsTo(CheckoutCategory::class);
    }
}
