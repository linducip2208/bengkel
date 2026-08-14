<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['claim_number', 'service_id', 'customer_id', 'vehicle_id', 'insurance_company', 'policy_number', 'claim_date', 'estimated_amount', 'approved_amount', 'status', 'notes'])]
class InsuranceClaim extends Model
{
    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'estimated_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
