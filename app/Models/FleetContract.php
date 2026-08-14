<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'name', 'start_date', 'end_date', 'service_interval_days', 'service_interval_km', 'notes', 'is_active'])]
class FleetContract extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'service_interval_days' => 'integer',
            'service_interval_km' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(FleetContractVehicle::class);
    }
}
