<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id', 'customer_id', 'name', 'phone', 'email',
    'vehicle_plate', 'vehicle_brand', 'vehicle_model',
    'booking_at', 'complaint', 'status', 'service_id', 'admin_notes',
])]
class Booking extends Model
{
    use HasBranchScope;

    protected $casts = ['booking_at' => 'datetime'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
}
