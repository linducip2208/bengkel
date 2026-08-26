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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['customer_id', 'vehicle_id', 'repair_category_id', 'title', 'description', 'service_date', 'charge', 'actual_cost', 'estimated_hours', 'started_at', 'completed_at', 'done_status', 'workflow_status', 'checked_in_at', 'qc_passed_at', 'mot_status', 'is_quotation', 'is_approved', 'created_by', 'branch_id', 'job_no', 'approval_token', 'repeat_of', 'assign_to', 'service_advisor_id', 'inspected_at', 'approved_at', 'invoiced_at', 'paid_at', 'released_at', 'cancelled_at', 'cancel_reason', 'survey_token'])]
class Service extends Model
{
    use HasBranchScope, HasFactory, SoftDeletes;

    public const WORKFLOW_LABELS = [
        0 => 'Booked', 1 => 'Checked In', 2 => 'Inspection', 3 => 'Waiting Approval',
        4 => 'Approved', 5 => 'In Progress', 6 => 'Waiting Parts', 7 => 'QC',
        8 => 'Ready', 9 => 'Invoiced', 10 => 'Paid', 11 => 'Released', 12 => 'Completed',
    ];

    public const WORKFLOW_TRANSITIONS = [
        0 => [1], 1 => [2], 2 => [3], 3 => [4], 4 => [5], 5 => [6, 7],
        6 => [5], 7 => [8], 8 => [9], 9 => [10], 10 => [11], 11 => [12], 12 => [],
    ];

    public function canTransitionTo(int $target): bool
    {
        return in_array($target, self::WORKFLOW_TRANSITIONS[(int) $this->workflow_status] ?? [], true);
    }

    protected function casts(): array
    {
        return [
            'service_date' => 'datetime',
            'charge' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'estimated_hours' => 'decimal:1',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'inspected_at' => 'datetime',
            'approved_at' => 'datetime',
            'invoiced_at' => 'datetime',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function serviceAdvisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_advisor_id');
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

    public function reservations(): HasMany
    {
        return $this->hasMany(PartReservation::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function getCostVarianceAttribute(): float
    {
        return (float) ($this->actual_cost ?? 0) - (float) ($this->charge ?? 0);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->workflow_status !== null) {
            return self::WORKFLOW_LABELS[(int) $this->workflow_status] ?? 'Booked';
        }

        return match ((int) $this->done_status) {
            0 => 'Pending', 1 => 'In Progress', 2 => 'Done',
            default => 'Pending',
        };
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->workflow_status !== null) {
            return match ((int) $this->workflow_status) {
                0 => 'secondary', 1 => 'info', 2 => 'primary',
                3 => 'warning', 4 => 'success', 5 => 'primary',
                6 => 'danger', 7 => 'info', 8 => 'teal',
                9 => 'purple', 10 => 'success', 11 => 'dark',
                12 => 'success',
                default => 'secondary',
            };
        }

        return match ((int) $this->done_status) {
            0 => 'secondary', 1 => 'warning', 2 => 'success',
            default => 'secondary',
        };
    }

    public function getWorkflowLabelAttribute(): string
    {
        return $this->status_label;
    }

    public function getWorkflowColorAttribute(): string
    {
        return $this->status_color;
    }

    public function scopeOpen($query)
    {
        return $query->where('workflow_status', '<', 12);
    }

    public function scopeInProgress($query)
    {
        return $query->whereBetween('workflow_status', [1, 11]);
    }

    public function scopeDone($query)
    {
        return $query->where('workflow_status', 12);
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
        if (! $this->duration) {
            if ($this->started_at && ! $this->completed_at) {
                $elapsed = round(now()->diffInMinutes($this->started_at) / 60, 1);

                return $elapsed.' jam (berjalan)';
            }

            return $this->estimated_hours ? $this->estimated_hours.' jam (estimasi)' : '-';
        }

        return $this->duration.' jam';
    }

    public function getIsOverdueAttribute(): bool
    {
        if (! $this->estimated_hours || ! $this->started_at || $this->completed_at) {
            return false;
        }

        return now()->diffInHours($this->started_at) > $this->estimated_hours;
    }

    public function repeatOf(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'repeat_of');
    }

    public function getOrCreateApprovalToken(): string
    {
        if (! empty($this->approval_token)) {
            return $this->approval_token;
        }

        $this->approval_token = $this->generateUniqueApprovalToken();
        $this->save();

        return $this->approval_token;
    }

    protected function generateUniqueApprovalToken(): string
    {
        do {
            $token = Str::random(32);
        } while (static::withoutGlobalScopes()->where('approval_token', $token)->exists());

        return $token;
    }

    public function detectRepeatJob(): ?Service
    {
        if (! $this->vehicle_id) {
            return null;
        }

        $cutoff = now()->subDays(30);

        return static::withoutGlobalScopes()
            ->where('vehicle_id', $this->vehicle_id)
            ->where('id', '!=', $this->id)
            ->where('done_status', 2)
            ->where('completed_at', '>=', $cutoff)
            ->where(function ($q) {
                $q->where('repair_category_id', $this->repair_category_id);
                if ($this->title) {
                    $q->orWhere('title', 'like', '%'.$this->title.'%');
                }
                if ($this->description) {
                    $q->orWhere('description', 'like', '%'.$this->description.'%');
                }
            })
            ->orderBy('completed_at', 'desc')
            ->first();
    }

    public function isRepeatJob(): bool
    {
        return $this->detectRepeatJob() !== null;
    }

    public function getIsRepeatJobAttribute(): bool
    {
        return $this->repeat_of !== null;
    }
}
