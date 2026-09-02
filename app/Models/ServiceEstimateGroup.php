<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One estimate = one or more groups. Each group carries the customer's
 * per-work-package decision (pending|approved|rejected).
 *
 * @property int $id
 * @property int $service_estimate_id
 * @property int|null $service_work_package_id
 * @property int|null $service_finding_id
 * @property string $title
 * @property string|null $severity_snapshot
 * @property int $standard_minutes
 * @property float $subtotal
 * @property float $grand_total
 * @property string $customer_decision
 * @property string|null $decision_reason
 * @property CarbonInterface|null $decided_at
 */
#[Fillable([
    'service_estimate_id', 'service_work_package_id', 'service_finding_id',
    'title', 'severity_snapshot', 'standard_minutes', 'subtotal', 'grand_total',
    'customer_decision', 'decision_reason', 'decided_at', 'sort_order',
])]
class ServiceEstimateGroup extends Model
{
    public const DECISION_PENDING = 'pending';

    public const DECISION_APPROVED = 'approved';

    public const DECISION_REJECTED = 'rejected';

    public const DECISION_LABELS = [
        self::DECISION_PENDING => 'Menunggu',
        self::DECISION_APPROVED => 'Disetujui',
        self::DECISION_REJECTED => 'Ditolak',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'decided_at' => 'datetime',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(ServiceEstimate::class, 'service_estimate_id');
    }

    public function workPackage(): BelongsTo
    {
        return $this->belongsTo(ServiceWorkPackage::class, 'service_work_package_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(ServiceFinding::class, 'service_finding_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceEstimateItem::class, 'estimate_group_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
