<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $odometer_in
 * @property int|null $odometer_out
 */
#[Fillable(['service_id', 'jobcard_no', 'customer_id', 'vehicle_id', 'odometer_in', 'odometer_out', 'in_date', 'out_date', 'next_service_date', 'next_service_km', 'done_status', 'reminder_sent'])]
class JobcardDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'jobcard_details';

    protected function casts(): array
    {
        return [
            'in_date' => 'datetime',
            'out_date' => 'datetime',
            'next_service_date' => 'date',
            'done_status' => 'integer',
            'reminder_sent' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
