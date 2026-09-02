<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One inspection result row for a checklist point.
 *
 * @property int $id
 * @property int $service_id
 * @property int $observation_point_id
 * @property bool $checked
 * @property string $condition_status
 * @property float|null $measurement_value
 * @property string|null $measurement_unit
 * @property string|null $comment
 */
#[Fillable(['service_id', 'observation_point_id', 'checked', 'comment', 'condition_status', 'measurement_value', 'measurement_unit'])]
class ServiceObservationPoint extends Model
{
    use HasFactory, SoftDeletes;

    public const CONDITION_NOT_CHECKED = 'not_checked';

    public const CONDITION_OK = 'ok';

    public const CONDITION_ATTENTION = 'attention';

    public const CONDITION_REPAIR_REQUIRED = 'repair_required';

    public const CONDITION_CRITICAL = 'critical';

    public const CONDITIONS = [
        self::CONDITION_NOT_CHECKED,
        self::CONDITION_OK,
        self::CONDITION_ATTENTION,
        self::CONDITION_REPAIR_REQUIRED,
        self::CONDITION_CRITICAL,
    ];

    public const CONDITION_LABELS = [
        self::CONDITION_NOT_CHECKED => 'Belum Diperiksa',
        self::CONDITION_OK => 'OK',
        self::CONDITION_ATTENTION => 'Perlu Perhatian',
        self::CONDITION_REPAIR_REQUIRED => 'Perlu Perbaikan',
        self::CONDITION_CRITICAL => 'Kritis',
    ];

    public const CONDITION_BADGE_COLORS = [
        self::CONDITION_NOT_CHECKED => 'secondary',
        self::CONDITION_OK => 'success',
        self::CONDITION_ATTENTION => 'warning',
        self::CONDITION_REPAIR_REQUIRED => 'orange',
        self::CONDITION_CRITICAL => 'danger',
    ];

    /** Conditions that must generate a finding. */
    public const FINDING_CONDITIONS = [
        self::CONDITION_ATTENTION,
        self::CONDITION_REPAIR_REQUIRED,
        self::CONDITION_CRITICAL,
    ];

    protected function casts(): array
    {
        return [
            'checked' => 'boolean',
            'measurement_value' => 'decimal:3',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function observationPoint(): BelongsTo
    {
        return $this->belongsTo(ObservationPoint::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(ServiceFinding::class, 'service_observation_point_id');
    }

    /** Evidence photos via the existing MediaAttachment infrastructure. */
    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }

    public function isActive(): bool
    {
        return $this->condition_status !== self::CONDITION_NOT_CHECKED;
    }

    public function conditionLabel(): string
    {
        return self::CONDITION_LABELS[$this->condition_status] ?? ucfirst($this->condition_status);
    }

    public function conditionBadgeColor(): string
    {
        return self::CONDITION_BADGE_COLORS[$this->condition_status] ?? 'secondary';
    }

    /** Rendered measurement, e.g. "1.2 mm". */
    public function measurementLabel(): ?string
    {
        if ($this->measurement_value === null) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $this->measurement_value, 3, '.', ''), '0'), '.')
            .($this->measurement_unit !== null ? ' '.$this->measurement_unit : '');
    }
}
