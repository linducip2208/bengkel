<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['customer_id', 'vehicle_id', 'service_id', 'reminder_type', 'reminder_date', 'sent', 'sent_at', 'branch_id', 'created_by', 'message'])]
class Reminder extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected function casts(): array
    {
        return [
            'reminder_date' => 'date',
            'sent' => 'boolean',
            'sent_at' => 'datetime',
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
