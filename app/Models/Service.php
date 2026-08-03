<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['customer_id', 'vehicle_id', 'repair_category_id', 'title', 'description', 'service_date', 'charge', 'estimated_hours', 'started_at', 'completed_at', 'done_status', 'mot_status', 'is_quotation', 'is_approved', 'created_by', 'branch_id', 'job_no'])]
class Service extends Model
{
    use HasFactory, SoftDeletes, HasBranchScope;

    protected function casts(): array
    {
        return [
            'service_date' => 'datetime',
            'charge' => 'decimal:2',
            'estimated_hours' => 'decimal:1',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'done_status' => 'integer',
            'mot_status' => 'boolean',
            'is_quotation' => 'boolean',
            'is_approved' => 'boolean',
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

    public function repairCategory(): BelongsTo
    {
        return $this->belongsTo(RepairCategory::class);
    }

    public function jobcardDetail(): HasOne
    {
        return $this->hasOne(JobcardDetail::class);
    }

    public function serviceObservationPoints(): HasMany
    {
        return $this->hasMany(ServiceObservationPoint::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class);
    }

    public function checkoutResults(): HasMany
    {
        return $this->hasMany(CheckoutResult::class);
    }

    public function serviceTaxes(): HasMany
    {
        return $this->hasMany(ServiceTax::class);
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_technicians')->withTimestamps();
    }

    public function serviceTechnicians(): HasMany
    {
        return $this->hasMany(ServiceTechnician::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('done_status', [0, 1]);
    }

    public function scopeInProgress($query)
    {
        return $query->where('done_status', 1);
    }

    public function scopeDone($query)
    {
        return $query->where('done_status', 2);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('service_date', today());
    }

    public function getDurationAttribute(): ?float
    {
        if ($this->started_at && $this->completed_at) {
            return round($this->started_at->diffInMinutes($this->completed_at) / 60, 1);
        }
        return null;
    }

    public function getDurationLabelAttribute(): string
    {
        if (!$this->duration) {
            if ($this->started_at && !$this->completed_at) {
                $elapsed = round(now()->diffInMinutes($this->started_at) / 60, 1);
                return $elapsed . ' jam (berjalan)';
            }
            return $this->estimated_hours ? $this->estimated_hours . ' jam (estimasi)' : '-';
        }
        return $this->duration . ' jam';
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->estimated_hours || !$this->started_at || $this->completed_at) {
            return false;
        }
        return now()->diffInHours($this->started_at) > $this->estimated_hours;
    }
}
