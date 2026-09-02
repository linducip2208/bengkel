<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Technical finding between checklist and commercial estimate.
 * A finding is a technical observation — it never carries a price.
 *
 * @property int $id
 * @property int $service_id
 * @property int|null $service_observation_point_id
 * @property int|null $branch_id
 * @property string $finding_number
 * @property string $severity
 * @property string $title
 * @property string|null $description
 * @property string|null $technician_note
 * @property string|null $recommendation
 * @property float|null $measurement_value
 * @property string|null $measurement_unit
 * @property string $status
 * @property CarbonInterface|null $resolved_at
 * @property int|null $resolved_by
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'service_id', 'service_observation_point_id', 'branch_id', 'finding_number',
    'severity', 'title', 'description', 'technician_note', 'recommendation',
    'measurement_value', 'measurement_unit', 'status', 'resolved_at', 'resolved_by',
    'created_by', 'updated_by',
])]
class ServiceFinding extends Model
{
    use HasBranchScope, SoftDeletes;

    public const SEVERITY_ATTENTION = 'attention';

    public const SEVERITY_REPAIR_REQUIRED = 'repair_required';

    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITIES = [
        self::SEVERITY_ATTENTION,
        self::SEVERITY_REPAIR_REQUIRED,
        self::SEVERITY_CRITICAL,
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_WORK_PROPOSED = 'work_proposed';

    public const STATUS_APPROVED_FOR_WORK = 'approved_for_work';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DEFERRED = 'deferred';

    public const STATUS_CANCELLED = 'cancelled';

    public const SEVERITY_LABELS = [
        self::SEVERITY_ATTENTION => 'Perlu Perhatian',
        self::SEVERITY_REPAIR_REQUIRED => 'Perlu Perbaikan',
        self::SEVERITY_CRITICAL => 'Kritis',
    ];

    public const SEVERITY_COLORS = [
        self::SEVERITY_ATTENTION => 'warning',
        self::SEVERITY_REPAIR_REQUIRED => 'orange',
        self::SEVERITY_CRITICAL => 'danger',
    ];

    public const SEVERITY_BADGES = [
        self::SEVERITY_ATTENTION => '🟡',
        self::SEVERITY_REPAIR_REQUIRED => '🟠',
        self::SEVERITY_CRITICAL => '🔴',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function observationPointResult(): BelongsTo
    {
        return $this->belongsTo(ServiceObservationPoint::class, 'service_observation_point_id');
    }

    public function workPackages(): HasMany
    {
        return $this->hasMany(ServiceWorkPackage::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_DEFERRED, self::STATUS_CANCELLED], true);
    }
}
