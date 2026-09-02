<?php

namespace App\Models;

use App\Services\SettingsService;
use App\Traits\HasBranchScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Service estimate (quotation) — commercial document, immutable once issued.
 *
 * @property int $id
 * @property string $estimate_number
 * @property int $service_id
 * @property int|null $customer_id
 * @property int|null $vehicle_id
 * @property int|null $branch_id
 * @property int $version
 * @property int|null $previous_estimate_id
 * @property string $status
 * @property CarbonInterface|null $estimate_date
 * @property CarbonInterface|null $valid_until
 * @property float $subtotal
 * @property float $discount
 * @property string $discount_type
 * @property float $tax_amount
 * @property float $grand_total
 * @property string|null $notes
 * @property string|null $terms
 * @property string|null $internal_notes
 * @property array|null $snapshot
 * @property string|null $public_token
 * @property string|null $approval_method
 * @property string|null $approval_ip
 * @property string|null $approval_user_agent
 * @property string|null $approved_hash
 * @property CarbonInterface|null $approved_at
 * @property CarbonInterface|null $rejected_at
 * @property string|null $rejection_reason
 * @property CarbonInterface|null $sent_at
 * @property CarbonInterface|null $converted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property Service|null $service
 * @property Customer|null $customer
 * @property Vehicle|null $vehicle
 * @property Collection<int, ServiceEstimateItem> $items
 * @property Collection<int, ServiceEstimateGroup> $groups
 * @property ServiceEstimate|null $previousEstimate
 * @property Invoice|null $invoice
 */
#[Fillable([
    'estimate_number', 'service_id', 'customer_id', 'vehicle_id', 'branch_id',
    'version', 'previous_estimate_id', 'status',
    'estimate_date', 'valid_until',
    'subtotal', 'discount', 'discount_type', 'tax_amount', 'grand_total',
    'notes', 'terms', 'internal_notes',
    'snapshot', 'public_token',
    'approval_method', 'approval_ip', 'approval_user_agent', 'approved_hash', 'approved_at',
    'rejected_at', 'rejection_reason', 'sent_at', 'converted_at',
    'created_by', 'updated_by',
    'decision_status', 'approved_total', 'rejected_total', 'decision_evidence',
])]
class ServiceEstimate extends Model
{
    use HasBranchScope, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_WAITING_APPROVAL = 'waiting_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';

    /** Derived commercial decision status (from per-group customer decisions). */
    public const DECISION_PENDING = 'pending';

    public const DECISION_PARTIALLY_APPROVED = 'partially_approved';

    public const DECISION_APPROVED = 'approved';

    public const DECISION_REJECTED = 'rejected';

    /** Commercial statuses that can still be acted upon by the workshop. */
    public const ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_WAITING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_PARTIALLY_APPROVED,
    ];

    /** Statuses a customer may still approve/reject. */
    public const APPROVABLE_STATUSES = [
        self::STATUS_SENT,
        self::STATUS_WAITING_APPROVAL,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SENT => 'Terkirim',
        self::STATUS_WAITING_APPROVAL => 'Menunggu Persetujuan',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_PARTIALLY_APPROVED => 'Sebagian Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_EXPIRED => 'Kedaluwarsa',
        self::STATUS_SUPERSEDED => 'Digantikan',
        self::STATUS_CONVERTED => 'Jadi Invoice',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'secondary',
        self::STATUS_SENT => 'info',
        self::STATUS_WAITING_APPROVAL => 'warning',
        self::STATUS_APPROVED => 'success',
        self::STATUS_PARTIALLY_APPROVED => 'info',
        self::STATUS_REJECTED => 'danger',
        self::STATUS_EXPIRED => 'dark',
        self::STATUS_SUPERSEDED => 'secondary',
        self::STATUS_CONVERTED => 'primary',
    ];

    protected function casts(): array
    {
        return [
            'estimate_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'version' => 'integer',
            'snapshot' => 'array',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'converted_at' => 'datetime',
            'approved_total' => 'decimal:2',
            'rejected_total' => 'decimal:2',
            'decision_evidence' => 'array',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

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

    public function items(): HasMany
    {
        return $this->hasMany(ServiceEstimateItem::class, 'service_estimate_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function previousEstimate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_estimate_id');
    }

    /** Invoice produced from this estimate (read accessor, set on conversion). */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /** Work-package groups within this estimate (per-package approval). */
    public function groups(): HasMany
    {
        return $this->hasMany(ServiceEstimateGroup::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'previous_estimate_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ------------------------------------------------------------------
    // State helpers
    // ------------------------------------------------------------------

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isIssued(): bool
    {
        return $this->status !== self::STATUS_DRAFT && ! is_null($this->snapshot);
    }

    public function isExpiredByDate(): bool
    {
        return in_array($this->status, self::APPROVABLE_STATUSES, true)
            && $this->valid_until !== null
            && $this->valid_until->copy()->startOfDay()->isPast();
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getOrCreatePublicToken(): string
    {
        if (! empty($this->public_token)) {
            return $this->public_token;
        }

        do {
            $token = Str::random(40);
        } while (static::withoutGlobalScopes()->where('public_token', $token)->exists());

        $this->forceFill(['public_token' => $token])->save();

        return $token;
    }

    // ------------------------------------------------------------------
    // Snapshot accessors (immutable after issue; live data only for drafts)
    // ------------------------------------------------------------------

    public function snapshotCompany(): array
    {
        $company = $this->snapshot['company'] ?? null;

        return is_array($company) ? $company : app(SettingsService::class)->getCompanyInfo();
    }

    public function snapshotCustomer(): array
    {
        if (isset($this->snapshot['customer']) && is_array($this->snapshot['customer'])) {
            return $this->snapshot['customer'];
        }

        return [
            'name' => $this->customer?->name,
            'phone' => $this->customer?->phone,
            'email' => $this->customer?->email,
            'address' => $this->customer?->address,
        ];
    }

    public function snapshotVehicle(): array
    {
        if (isset($this->snapshot['vehicle']) && is_array($this->snapshot['vehicle'])) {
            return $this->snapshot['vehicle'];
        }

        $vehicle = $this->vehicle;

        return [
            'number_plate' => $vehicle?->number_plate,
            'type' => $vehicle?->vehicleType?->vehicle_type,
            'brand' => $vehicle?->vehicleBrand?->vehicle_brand,
            'model' => $vehicle?->model_name,
            'year' => $vehicle?->model_year,
            'odometer' => $vehicle?->odometer,
        ];
    }

    public function snapshotService(): array
    {
        if (isset($this->snapshot['service']) && is_array($this->snapshot['service'])) {
            return $this->snapshot['service'];
        }

        $service = $this->service;
        $km = $service !== null && $service->jobcardDetail !== null
            ? $service->jobcardDetail->odometer_in
            : $this->vehicle?->odometer;

        return [
            'number' => $service?->job_no,
            'title' => $service?->title,
            'description' => $service?->description,
            'km' => $km,
        ];
    }
}
