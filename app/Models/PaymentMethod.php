<?php

namespace App\Models;

use App\Models\Concerns\HasRaceSafeUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['payment', 'slug', 'description', 'is_active'])]
class PaymentMethod extends Model
{
    use HasFactory, HasRaceSafeUniqueSlug, SoftDeletes;

    protected const UNIQUE_SLUG_SOURCE = 'payment';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentRecords(): HasMany
    {
        return $this->hasMany(PaymentRecord::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->payment;
    }
}
