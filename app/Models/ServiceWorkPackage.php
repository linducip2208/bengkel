<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WORK PACKAGE — the commercial/operational bridge between a technical
 * finding and the customer estimate. Labor + parts + standard time,
 * all money server-computed and persisted as DECIMAL.
 *
 * @property int $id
 * @property int $service_id
 * @property int|null $service_finding_id
 * @property string $title
 * @property string|null $severity_snapshot
 * @property int $standard_minutes
 * @property string $status
 */
#[Fillable([
    'service_id', 'service_finding_id', 'branch_id', 'title', 'description',
    'severity_snapshot', 'standard_minutes', 'status', 'created_by', 'updated_by',
])]
class ServiceWorkPackage extends Model
{
    use HasBranchScope, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PROPOSED = 'proposed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_QC_PASSED = 'qc_passed';

    public const STATUS_QC_FAILED = 'qc_failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PROPOSED => 'Di Proposal',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_IN_PROGRESS => 'Dikerjakan',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_QC_PASSED => 'QC Lolos',
        self::STATUS_QC_FAILED => 'QC Gagal',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(ServiceFinding::class, 'service_finding_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceWorkPackageItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function task(): HasOne
    {
        return $this->hasOne(ServiceWorkTask::class);
    }

    public function qcChecks(): HasMany
    {
        return $this->hasMany(ServiceWorkQcCheck::class)->orderByDesc('checked_at');
    }

    public function latestQcCheck(): ?ServiceWorkQcCheck
    {
        /** @var ServiceWorkQcCheck|null $first */
        $first = $this->qcChecks()->first();

        return $first;
    }

    /** Server-authoritative totals from persisted item rows. */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PROPOSED], true);
    }

    public function computeTotals(): array
    {
        $labor = 0.0;
        $part = 0.0;
        $other = 0.0;
        $itemMinutes = 0;

        foreach ($this->items()->get() as $item) {
            /** @var ServiceWorkPackageItem $item */
            $line = round((float) $item->quantity * (float) $item->unit_price, 2);
            match ($item->item_type) {
                'labor' => $labor += $line,
                'part' => $part += $line,
                default => $other += $line,
            };
            $itemMinutes += (int) $item->standard_minutes;
        }

        // Package-level standard time wins; item minutes are the fallback.
        $minutes = (int) $this->standard_minutes > 0 ? (int) $this->standard_minutes : $itemMinutes;

        return [
            'labor_total' => round($labor, 2),
            'part_total' => round($part, 2),
            'other_total' => round($other, 2),
            'grand_total' => round($labor + $part + $other, 2),
            'standard_minutes' => $minutes,
        ];
    }
}
