<?php

namespace App\Models;

use App\Traits\HasBranchScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Operational execution of an APPROVED work package.
 * One task per package (DB unique) — approval retries stay idempotent.
 *
 * @property int $id
 * @property int $service_id
 * @property int $service_work_package_id
 * @property int|null $assigned_to
 * @property string $status
 * @property int $standard_minutes
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $completed_at
 */
#[Fillable([
    'service_id', 'service_work_package_id', 'branch_id', 'assigned_to',
    'status', 'standard_minutes', 'started_at', 'completed_at', 'created_by', 'updated_by',
])]
class ServiceWorkTask extends Model
{
    use HasBranchScope;

    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_QC_PENDING = 'qc_pending';

    public const STATUS_QC_PASSED = 'qc_passed';

    public const STATUS_QC_FAILED = 'qc_failed';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_READY => 'Siap Dikerjakan',
        self::STATUS_IN_PROGRESS => 'Sedang Dikerjakan',
        self::STATUS_PAUSED => 'Dijeda',
        self::STATUS_COMPLETED => 'Selesai Dikerjakan',
        self::STATUS_QC_PENDING => 'Menunggu QC',
        self::STATUS_QC_PASSED => 'QC Lolos',
        self::STATUS_QC_FAILED => 'QC Gagal',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function workPackage(): BelongsTo
    {
        return $this->belongsTo(ServiceWorkPackage::class, 'service_work_package_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(ServiceWorkTimeEntry::class)->orderBy('started_at');
    }

    public function openEntry(): ?ServiceWorkTimeEntry
    {
        /** @var ServiceWorkTimeEntry|null $entry */
        $entry = $this->timeEntries()->whereNull('ended_at')->first();

        return $entry;
    }

    /** Actual working seconds — excludes paused duration. */
    public function actualSeconds(): int
    {
        $total = 0;
        /** @var ServiceWorkTimeEntry $entry */
        foreach ($this->timeEntries()->get() as $entry) {
            $total += $entry->effectiveSeconds();
        }

        return $total;
    }

    public function actualMinutes(): int
    {
        return (int) round($this->actualSeconds() / 60);
    }
}
