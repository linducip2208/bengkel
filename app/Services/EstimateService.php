<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateGroup;
use App\Models\ServiceEstimateItem;
use App\Models\ServiceWorkPackage;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Estimate domain service.
 *
 * The estimate is a commercial quotation document. It never creates
 * accounting entries, never touches stock, never creates receivables and
 * never carries payment state. Money is authoritative server-side.
 */
class EstimateService
{
    public function __construct(protected InvoiceService $invoiceService) {}

    // ------------------------------------------------------------------
    // Calculation (single source of truth for money)
    // ------------------------------------------------------------------

    public function lineDiscountAmount(float $quantity, float $unitPrice, float $discount, ?string $discountType): float
    {
        $base = $quantity * $unitPrice;

        if ($discountType === 'percent') {
            $discount = $base * ($discount / 100);
        }

        return min(round(max($discount, 0), 2), round($base, 2));
    }

    /**
     * @return array{base: float, discount: float, tax: float, line_total: float}
     */
    public function computeLine(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $base = round($quantity * $unitPrice, 2);
        $discount = $this->lineDiscountAmount(
            $quantity,
            $unitPrice,
            (float) ($item['discount'] ?? 0),
            $item['discount_type'] ?? 'fixed',
        );
        $taxRate = (float) ($item['tax_rate'] ?? 0);
        $tax = round(($base - $discount) * $taxRate / 100, 2);

        return [
            'base' => $base,
            'discount' => $discount,
            'tax' => $tax,
            'line_total' => round($base - $discount + $tax, 2),
        ];
    }

    /**
     * Recompute all lines and document totals server-side.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, discount: float, tax: float, grand_total: float}
     */
    public function computeTotals(array $items, float $headerDiscount = 0.0, string $headerDiscountType = 'fixed', ?float $taxPercent = null): array
    {
        $computed = [];
        $linesSubtotal = 0.0;
        $linesDiscount = 0.0;
        $linesTax = 0.0;

        foreach ($items as $item) {
            $calc = $this->computeLine($item);
            $linesSubtotal += $calc['base'];
            $linesDiscount += $calc['discount'];
            $linesTax += $calc['tax'];

            $computed[] = array_merge($item, [
                'discount' => $calc['discount'],
                'discount_type' => $item['discount_type'] ?? 'fixed',
                'tax_amount' => $calc['tax'],
                'line_total' => $calc['line_total'],
            ]);
        }

        $linesSubtotal = round($linesSubtotal, 2);

        // Header discount applies to the document as a whole.
        if ($headerDiscountType === 'percent') {
            $headerDiscount = $linesSubtotal * ($headerDiscount / 100);
        }
        $headerDiscount = round(min(max($headerDiscount, 0), $linesSubtotal - $linesDiscount), 2);

        // Optional document-level tax on top of the discounted subtotal
        // (item-level tax stays independent and is added separately).
        $docTax = 0.0;
        if ($taxPercent !== null && $taxPercent > 0) {
            $docTax = round(($linesSubtotal - $linesDiscount - $headerDiscount) * $taxPercent / 100, 2);
        }

        return [
            'items' => $computed,
            'subtotal' => $linesSubtotal,
            'discount' => round($linesDiscount + $headerDiscount, 2),
            'tax' => round($linesTax + $docTax, 2),
            'grand_total' => round($linesSubtotal - $linesDiscount - $headerDiscount + $linesTax + $docTax, 2),
        ];
    }

    // ------------------------------------------------------------------
    // Snapshot (immutable commercial record once issued)
    // ------------------------------------------------------------------

    public function buildSnapshot(ServiceEstimate $estimate): array
    {
        $company = app(SettingsService::class)->getCompanyInfo();
        $vehicle = $estimate->vehicle;
        $service = $estimate->service;

        return [
            'company' => [
                'name' => $company['name'] ?? config('app.name'),
                'address' => $company['address'] ?? '',
                'phone' => $company['phone'] ?? '',
                'email' => $company['email'] ?? '',
                'tax_id' => $company['tax_id'] ?? '',
                'logo' => $company['logo'] ?? '',
            ],
            'customer' => [
                'name' => $estimate->customer?->name,
                'phone' => $estimate->customer?->phone,
                'email' => $estimate->customer?->email,
                'address' => $estimate->customer?->address,
            ],
            'vehicle' => [
                'number_plate' => $vehicle?->number_plate,
                'type' => $vehicle?->vehicleType?->vehicle_type,
                'brand' => $vehicle?->vehicleBrand?->vehicle_brand,
                'model' => $vehicle?->model_name,
                'year' => $vehicle?->model_year,
                'odometer' => $vehicle?->odometer,
            ],
            'service' => [
                'number' => $service?->job_no,
                'title' => $service?->title,
                'description' => $service?->description,
                'km' => ($service !== null && $service->jobcardDetail !== null)
                    ? $service->jobcardDetail->odometer_in
                    : $vehicle?->odometer,
            ],
            'items' => $estimate->items->map(fn (ServiceEstimateItem $item) => [
                'item_type' => $item->item_type,
                'description' => $item->description,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'discount' => (string) $item->discount,
                'tax_rate' => $item->tax_rate !== null ? (string) $item->tax_rate : null,
                'tax_amount' => (string) $item->tax_amount,
                'line_total' => (string) $item->line_total,
            ])->all(),
            // WHY this work was recommended — finding/work-package evidence
            // survives master-data changes.
            'groups' => $estimate->groups()->get()->map(function (ServiceEstimateGroup $group) {
                return [
                    'title' => $group->title,
                    'severity_snapshot' => $group->severity_snapshot,
                    'standard_minutes' => $group->standard_minutes,
                    'grand_total' => (string) $group->grand_total,
                    'customer_decision' => $group->customer_decision,
                    'finding_number' => $group->finding?->finding_number,
                    'finding_title' => $group->finding?->title,
                    'finding_severity' => $group->finding?->severity,
                    'finding_measurement' => $group->finding?->measurement_value !== null
                        ? $group->finding->measurement_value.($group->finding->measurement_unit ? ' '.$group->finding->measurement_unit : '')
                        : null,
                    'work_package_title' => $group->workPackage?->title,
                ];
            })->all(),
        ];
    }

    public function contentHash(ServiceEstimate $estimate): string
    {
        $payload = [
            'number' => $estimate->estimate_number,
            'version' => $estimate->version,
            'subtotal' => (string) $estimate->subtotal,
            'discount' => (string) $estimate->discount,
            'tax' => (string) $estimate->tax_amount,
            'grand_total' => (string) $estimate->grand_total,
            'items' => $estimate->items->map(fn (ServiceEstimateItem $i) => [
                $i->item_type,
                $i->description,
                (string) $i->quantity,
                (string) $i->unit_price,
                (string) $i->line_total,
            ])->all(),
        ];

        return hash('sha256', json_encode($payload));
    }

    // ------------------------------------------------------------------
    // Create / update (draft editing NEVER creates new rows)
    // ------------------------------------------------------------------

    /**
     * Create a draft estimate for a service. Duplicate submissions are
     * idempotent: if the service already has a DRAFT, that exact row is
     * updated in place (same primary key, no new row).
     *
     * $packages = work-package ids to attach as approval groups.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, int>  $packages
     */
    public function createDraft(Service $service, array $data, array $items, array $packages = []): ServiceEstimate
    {
        return DB::transaction(function () use ($service, $data, $items, $packages) {
            // Serialize concurrent create/update submissions per service.
            Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();

            $existing = ServiceEstimate::where('service_id', $service->id)
                ->where('status', ServiceEstimate::STATUS_DRAFT)
                ->orderByDesc('id')
                ->first();

            if ($existing !== null) {
                return $this->updateDraft($existing, $data, $items, $packages);
            }

            return $this->storeNewDraft($service, $data, $items, $packages);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, int>  $packages
     */
    protected function storeNewDraft(Service $service, array $data, array $items, array $packages = []): ServiceEstimate
    {
        $totals = $this->computeTotals(
            $items,
            (float) ($data['discount'] ?? 0),
            $data['discount_type'] ?? 'fixed',
            isset($data['tax_percent']) && $data['tax_percent'] !== '' ? (float) $data['tax_percent'] : null,
        );

        $estimate = ServiceEstimate::create([
            'estimate_number' => DocumentNumberService::generate(DocumentNumberService::ESTIMATES, 'EST', 'Ym', 4),
            'service_id' => $service->id,
            'customer_id' => $service->customer_id,
            'vehicle_id' => $service->vehicle_id,
            'branch_id' => $service->branch_id,
            'version' => 1,
            'status' => ServiceEstimate::STATUS_DRAFT,
            'estimate_date' => $data['estimate_date'] ?? now()->toDateString(),
            'valid_until' => $data['valid_until'] ?? now()->addDays(7)->toDateString(),
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'discount_type' => $data['discount_type'] ?? 'fixed',
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand_total'],
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? $this->defaultTerms(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->persistItems($estimate, $totals['items']);

        foreach (array_unique(array_map('intval', $packages)) as $packageId) {
            $package = ServiceWorkPackage::query()->whereKey($packageId)->first();
            if ($package !== null && (int) $package->service_id === (int) $service->id) {
                $this->flow()->addWorkPackageToEstimate($estimate, $package);
            }
        }

        if (count($packages) > 0) {
            $this->flow()->recalculateEstimateFromGroups($estimate);
        }

        $estimate->refresh();

        ActivityLog::record('estimate.created', $estimate, "Estimasi {$estimate->estimate_number} v{$estimate->version} dibuat", [
            'grand_total' => (string) $totals['grand_total'],
            'version' => 1,
        ]);

        return $estimate;
    }

    /**
     * Edit a DRAFT estimate in place. The primary key never changes and no
     * additional estimate row is created — revisions are a separate, explicit
     * operation (revise()).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, int>  $packages
     */
    public function updateDraft(ServiceEstimate $estimate, array $data, array $items, array $packages = []): ServiceEstimate
    {
        return DB::transaction(function () use ($estimate, $data, $items, $packages) {
            $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->firstOrFail();

            abort_unless($locked->isEditable(), 422, 'Estimasi sudah dikirim/disetujui — gunakan Buat Revisi untuk mengubahnya.');

            $totals = $this->computeTotals(
                $items,
                (float) ($data['discount'] ?? 0),
                $data['discount_type'] ?? 'fixed',
                isset($data['tax_percent']) && $data['tax_percent'] !== '' ? (float) $data['tax_percent'] : null,
            );

            $locked->update([
                'estimate_date' => $data['estimate_date'] ?? $locked->estimate_date,
                'valid_until' => $data['valid_until'] ?? $locked->valid_until,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'discount_type' => $data['discount_type'] ?? 'fixed',
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'notes' => $data['notes'] ?? $locked->notes,
                'terms' => $data['terms'] ?? $locked->terms,
                'updated_by' => auth()->id(),
            ]);

            $locked->items()->delete();
            $this->persistItems($locked, $totals['items']);
            $locked->refresh();

            // Rebuild approval groups from the submitted package selection.
            ServiceEstimateGroup::where('service_estimate_id', $locked->id)->delete();
            foreach (array_unique(array_map('intval', $packages)) as $packageId) {
                $package = ServiceWorkPackage::query()->whereKey($packageId)->first();
                if ($package !== null && (int) $package->service_id === (int) $locked->service_id) {
                    $this->flow()->addWorkPackageToEstimate($locked, $package);
                }
            }

            if (count($packages) > 0) {
                $this->flow()->recalculateEstimateFromGroups($locked);
            }

            $locked->refresh();

            ActivityLog::record('estimate.updated', $locked, "Estimasi {$locked->estimate_number} v{$locked->version} diperbarui", [
                'old_total' => (string) $estimate->grand_total,
                'new_total' => (string) $locked->grand_total,
                'version' => $locked->version,
            ]);

            return $locked;
        });
    }

    protected function persistItems(ServiceEstimate $estimate, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $estimate->items()->create([
                'product_id' => ! empty($item['product_id']) ? (int) $item['product_id'] : null,
                'item_type' => in_array($item['item_type'] ?? null, [ServiceEstimateItem::TYPE_PART, ServiceEstimateItem::TYPE_LABOR, ServiceEstimateItem::TYPE_OTHER], true)
                    ? $item['item_type']
                    : ServiceEstimateItem::TYPE_OTHER,
                'description' => $item['description'],
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'discount' => $item['discount'] ?? 0,
                'discount_type' => $item['discount_type'] ?? 'fixed',
                'tax_rate' => isset($item['tax_rate']) && $item['tax_rate'] !== '' ? (float) $item['tax_rate'] : null,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'line_total' => $item['line_total'] ?? 0,
                'estimate_group_id' => ! empty($item['estimate_group_id']) ? (int) $item['estimate_group_id'] : null,
                'sort_order' => $index,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Lifecycle: send / approve / reject / expire
    // ------------------------------------------------------------------

    public function markSent(ServiceEstimate $estimate, string $channel, ?string $note = null): ServiceEstimate
    {
        return DB::transaction(function () use ($estimate, $channel, $note) {
            $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ServiceEstimate::STATUS_DRAFT) {
                $locked->forceFill([
                    'snapshot' => $this->buildSnapshot($locked),
                    'status' => ServiceEstimate::STATUS_WAITING_APPROVAL,
                    'sent_at' => now(),
                    'public_token' => $locked->public_token ?: $this->freshToken(),
                    'updated_by' => auth()->id(),
                ])->save();

                $this->syncServiceToWaitingApproval($locked);

                ActivityLog::record('estimate.sent', $locked, "Estimasi {$locked->estimate_number} dikirim ({$channel})", [
                    'version' => $locked->version,
                    'channel' => $channel,
                    'grand_total' => (string) $locked->grand_total,
                    'note' => $note,
                ]);
            }

            return $locked;
        });
    }

    protected function freshToken(): string
    {
        do {
            $token = Str::random(40);
        } while (ServiceEstimate::withoutGlobalScopes()->where('public_token', $token)->exists());

        return $token;
    }

    protected function syncServiceToWaitingApproval(ServiceEstimate $estimate): void
    {
        /** @var Service|null $service */
        $service = Service::query()->whereKey($estimate->service_id)->lockForUpdate()->first();
        if ($service === null || $service->cancelled_at) {
            return;
        }

        if ((int) $service->workflow_status === 2) {
            $service->update(['workflow_status' => 3]);
        }
    }

    /**
     * Customer (or authorized staff) approves the CURRENT estimate version.
     * Idempotent: repeat clicks never duplicate events or state changes.
     */
    public function approve(ServiceEstimate $estimate, string $method, ?string $reason = null, bool $override = false): ServiceEstimate
    {
        return DB::transaction(function () use ($estimate, $method, $reason, $override) {
            $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ServiceEstimate::STATUS_APPROVED) {
                return $locked; // idempotent re-click
            }

            // Expire on touch: a lapsed waiting-approval estimate can no longer
            // be approved unless a manager explicitly overrides it.
            if ($locked->isExpiredByDate()) {
                $locked->forceFill(['status' => ServiceEstimate::STATUS_EXPIRED])->save();
                ActivityLog::record('estimate.expired', $locked, "Estimasi {$locked->estimate_number} v{$locked->version} kedaluwarsa", [
                    'version' => $locked->version,
                ]);
            }

            if ($locked->status === ServiceEstimate::STATUS_EXPIRED && ! $override) {
                abort(409, 'Estimasi sudah kedaluwarsa — buat revisi untuk memperbaruinya.');
            }

            if (! in_array($locked->status, [ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL, ServiceEstimate::STATUS_EXPIRED], true)) {
                abort(409, 'Estimasi tidak sedang menunggu persetujuan.');
            }

            $hash = $this->contentHash($locked);

            // Grouped estimates: override approval = approve ALL groups
            // (per-group partial decisions happen via submitGroupDecisions()).
            if ($locked->groups()->exists()) {
                $hasPending = $locked->groups()->where('customer_decision', ServiceEstimateGroup::DECISION_PENDING)->exists();
                if ($hasPending) {
                    ServiceEstimateGroup::where('service_estimate_id', $locked->id)
                        ->where('customer_decision', ServiceEstimateGroup::DECISION_PENDING)
                        ->update([
                            'customer_decision' => ServiceEstimateGroup::DECISION_APPROVED,
                            'decided_at' => now(),
                            'decision_reason' => $override ? 'Disetujui via override manager' : 'Disetujui keseluruhan',
                        ]);
                }
            }

            $locked->forceFill([
                'status' => ServiceEstimate::STATUS_APPROVED,
                'decision_status' => ServiceEstimate::DECISION_APPROVED,
                'approved_at' => now(),
                'approval_method' => $method,
                'approval_ip' => request()->ip(),
                'approval_user_agent' => substr((string) request()->userAgent(), 0, 255),
                'approved_hash' => $hash,
                'snapshot' => $locked->snapshot ?? $this->buildSnapshot($locked),
            ])->save();
            $this->recalculateApprovedAmounts($locked);
            $this->syncServiceApproved($locked);
            $this->flow()->createTasksForApprovedGroups($locked);
            $this->flow()->reservePartsForApprovedGroups($locked);

            $event = $override ? 'estimate.override_approved' : 'estimate.approved';
            ActivityLog::record($event, $locked, $override
                ? "Estimasi {$locked->estimate_number} v{$locked->version} disetujui (override internal).{$reason}"
                : "Estimasi {$locked->estimate_number} v{$locked->version} disetujui via {$method}.", [
                    'version' => $locked->version,
                    'grand_total' => (string) $locked->grand_total,
                    'approved_hash' => $hash,
                    'method' => $method,
                    'reason' => $reason,
                ]);

            return $locked;
        });
    }

    protected function syncServiceApproved(ServiceEstimate $estimate): void
    {
        /** @var Service|null $service */
        $service = Service::query()->whereKey($estimate->service_id)->lockForUpdate()->first();
        if ($service === null || $service->cancelled_at) {
            return;
        }

        // Only the newest active version governs the service state.
        $newerActive = ServiceEstimate::where('service_id', $service->id)
            ->where('version', '>', $estimate->version)
            ->whereIn('status', [ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL, ServiceEstimate::STATUS_APPROVED])
            ->exists();

        if ($newerActive) {
            return;
        }

        $service->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);
        if ((int) $service->workflow_status === 3) {
            $service->update(['workflow_status' => 4]);
        }

        // Source of truth before invoicing = approved amount only (never the
        // full grand_total when part of the estimate was rejected).
        $service->update(['charge' => $estimate->approved_total > 0 ? $estimate->approved_total : $estimate->grand_total]);
    }

    /** Derive approved/rejected amounts from group decisions (server-side). */
    public function recalculateApprovedAmounts(ServiceEstimate $estimate): void
    {
        $groups = ServiceEstimateGroup::where('service_estimate_id', $estimate->id)->get();

        if ($groups->isEmpty()) {
            $estimate->forceFill([
                'decision_status' => null,
                'approved_total' => 0,
                'rejected_total' => 0,
            ])->save();

            return;
        }

        $approvedTotal = round((float) $groups->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)->sum('grand_total'), 2);
        $rejectedTotal = round((float) $groups->where('customer_decision', ServiceEstimateGroup::DECISION_REJECTED)->sum('grand_total'), 2);
        $approvedCount = $groups->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)->count();
        $rejectedCount = $groups->where('customer_decision', ServiceEstimateGroup::DECISION_REJECTED)->count();
        $pendingCount = $groups->where('customer_decision', ServiceEstimateGroup::DECISION_PENDING)->count();

        $decisionStatus = ServiceEstimate::DECISION_PENDING;
        if ($pendingCount === 0) {
            if ($rejectedCount === 0) {
                $decisionStatus = ServiceEstimate::DECISION_APPROVED;
            } elseif ($approvedCount === 0) {
                $decisionStatus = ServiceEstimate::DECISION_REJECTED;
            } else {
                $decisionStatus = ServiceEstimate::DECISION_PARTIALLY_APPROVED;
            }
        }

        $estimate->forceFill([
            'decision_status' => $decisionStatus,
            'approved_total' => $approvedTotal,
            'rejected_total' => $rejectedTotal,
        ])->save();
    }

    public function reject(ServiceEstimate $estimate, ?string $reason = null): ServiceEstimate
    {
        return DB::transaction(function () use ($estimate, $reason) {
            $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ServiceEstimate::STATUS_REJECTED) {
                return $locked; // idempotent
            }

            if (! in_array($locked->status, [ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL, ServiceEstimate::STATUS_EXPIRED], true)) {
                abort(409, 'Estimasi tidak sedang menunggu persetujuan.');
            }

            $locked->forceFill([
                'status' => ServiceEstimate::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => $reason ? substr($reason, 0, 255) : null,
            ])->save();

            // Service stays in Waiting Approval for revision — never cancelled.
            ActivityLog::record('estimate.rejected', $locked, "Estimasi {$locked->estimate_number} v{$locked->version} ditolak", [
                'version' => $locked->version,
                'grand_total' => (string) $locked->grand_total,
                'reason' => $reason,
            ]);

            return $locked;
        });
    }

    /** Mark lapsed waiting-approval estimates as expired (scheduler). */
    public function expireLapsed(): int
    {
        $expired = ServiceEstimate::query()
            ->whereIn('status', [ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->get();

        foreach ($expired as $estimate) {
            DB::transaction(function () use ($estimate) {
                $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->first();
                if (! $locked || ! in_array($locked->status, [ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL], true)) {
                    return;
                }
                $locked->forceFill(['status' => ServiceEstimate::STATUS_EXPIRED])->save();
                ActivityLog::record('estimate.expired', $locked, "Estimasi {$locked->estimate_number} v{$locked->version} kedaluwarsa", [
                    'version' => $locked->version,
                ]);
            });
        }

        return $expired->count();
    }

    // ------------------------------------------------------------------
    // Revisions
    // ------------------------------------------------------------------

    /**
     * Create version N+1. The only operation that intentionally adds an
     * estimate row. The previous approved/sent version is kept untouched as
     * an immutable historical document.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function revise(ServiceEstimate $estimate, array $data, array $items, ?string $reason = null): ServiceEstimate
    {
        return DB::transaction(function () use ($estimate, $data, $items, $reason) {
            $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->firstOrFail();

            abort_if($locked->status === ServiceEstimate::STATUS_DRAFT, 422, 'Draft masih bisa diedit langsung — tidak perlu revisi.');
            abort_if($locked->status === ServiceEstimate::STATUS_SUPERSEDED, 422, 'Estimasi sudah digantikan versi lain.');
            abort_if($locked->status === ServiceEstimate::STATUS_CONVERTED, 422, 'Estimasi sudah menjadi invoice.');

            // No items submitted → carry the current commercial content forward.
            if (count($items) === 0) {
                $items = $locked->items->map(fn (ServiceEstimateItem $item) => [
                    'item_type' => $item->item_type,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount' => (float) $item->discount,
                    'discount_type' => $item->discount_type,
                    'tax_rate' => $item->tax_rate,
                    'estimate_group_id' => $item->estimate_group_id,
                ])->all();
            }

            $totals = $this->computeTotals(
                $items,
                (float) ($data['discount'] ?? 0),
                $data['discount_type'] ?? 'fixed',
                isset($data['tax_percent']) && $data['tax_percent'] !== '' ? (float) $data['tax_percent'] : null,
            );

            $latestVersion = (int) ServiceEstimate::withoutGlobalScopes()
                ->where('service_id', $locked->service_id)
                ->max('version');

            $revision = ServiceEstimate::create([
                'estimate_number' => DocumentNumberService::generate(DocumentNumberService::ESTIMATES, 'EST', 'Ym', 4),
                'service_id' => $locked->service_id,
                'customer_id' => $locked->customer_id,
                'vehicle_id' => $locked->vehicle_id,
                'branch_id' => $locked->branch_id,
                'version' => $latestVersion + 1,
                'previous_estimate_id' => $locked->id,
                'status' => ServiceEstimate::STATUS_DRAFT,
                'estimate_date' => $data['estimate_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? now()->addDays(7)->toDateString(),
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'discount_type' => $data['discount_type'] ?? 'fixed',
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'notes' => $data['notes'] ?? $locked->notes,
                'terms' => $data['terms'] ?? $locked->terms,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->persistItems($revision, $totals['items']);

            // Carry approval groups onto the new version — decisions reset to
            // pending (the customer decides again on the new prices), while the
            // superseded version keeps its historical decision evidence.
            foreach ($locked->groups()->orderBy('sort_order')->get() as $oldGroup) {
                /** @var ServiceEstimateGroup $oldGroup */
                $newGroup = ServiceEstimateGroup::create([
                    'service_estimate_id' => $revision->id,
                    'service_work_package_id' => $oldGroup->service_work_package_id,
                    'service_finding_id' => $oldGroup->service_finding_id,
                    'title' => $oldGroup->title,
                    'severity_snapshot' => $oldGroup->severity_snapshot,
                    'standard_minutes' => $oldGroup->standard_minutes,
                    'subtotal' => $oldGroup->subtotal,
                    'grand_total' => $oldGroup->grand_total,
                    'customer_decision' => ServiceEstimateGroup::DECISION_PENDING,
                    'sort_order' => $oldGroup->sort_order,
                ]);

                // Remap the carried-over items to the NEW group ids — the old
                // ids belong to the superseded version's groups.
                ServiceEstimateItem::where('service_estimate_id', $revision->id)
                    ->where('estimate_group_id', $oldGroup->id)
                    ->update(['estimate_group_id' => $newGroup->id]);
            }
            if ($locked->groups()->exists()) {
                $this->flow()->recalculateEstimateFromGroups($revision);
            }

            // Previous version keeps its history but is no longer current.
            $locked->forceFill(['status' => ServiceEstimate::STATUS_SUPERSEDED])->save();
            ActivityLog::record('estimate.superseded', $locked, "Estimasi {$locked->estimate_number} v{$locked->version} digantikan oleh v{$revision->version}", [
                'superseded_by' => $revision->id,
                'version' => $locked->version,
            ]);

            ActivityLog::record('estimate.revised', $revision, "Revisi estimasi {$locked->estimate_number}: v{$locked->version} → v{$revision->version} ({$revision->estimate_number})", [
                'previous_id' => $locked->id,
                'previous_status' => $locked->status,
                'previous_total' => (string) $locked->grand_total,
                'new_total' => (string) $revision->grand_total,
                'version' => $revision->version,
                'reason' => $reason,
            ]);

            return $revision;
        });
    }

    // ------------------------------------------------------------------
    // Conversion to invoice
    // ------------------------------------------------------------------

    /**
     * Copy the approved estimate into a real invoice. The estimate itself is
     * NOT mutated into the invoice — it stays as an immutable historical
     * commercial document (status becomes `converted`).
     */
    public function convertToInvoice(ServiceEstimate $estimate, array $overrides = []): Invoice
    {
        return DB::transaction(function () use ($estimate, $overrides) {
            $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->firstOrFail();
            $service = Service::query()->whereKey($locked->service_id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ServiceEstimate::STATUS_CONVERTED) {
                $existing = Invoice::where('service_estimate_id', $locked->id)->first();
                abort_if($existing === null, 409, 'Estimasi sudah dikonversi tetapi invoice tidak ditemukan.');

                return $existing; // idempotent
            }

            abort_unless(
                in_array($locked->status, [ServiceEstimate::STATUS_APPROVED, ServiceEstimate::STATUS_PARTIALLY_APPROVED], true),
                422,
                'Hanya estimasi yang (sebagian) disetujui yang bisa dibuatkan invoice.'
            );
            $invoiceGuard = app(WorkshopInvoiceGuard::class);
            $invoiceGuard->assertCanCreateServiceInvoice($service);
            abort_if(Invoice::where('service_estimate_id', $locked->id)->exists(), 409, 'Invoice dari estimasi ini sudah ada.');

            // ONLY APPROVED groups/items are invoiceable commercial work.
            // Rejected and pending groups never reach the invoice.
            $groups = ServiceEstimateGroup::where('service_estimate_id', $locked->id)->get();
            $approvedGroupIds = $groups->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)->pluck('id');

            /** @var Collection<int, ServiceEstimateItem> $estimateItems */
            $estimateItems = $locked->items()->get();
            $invoiceableItems = $estimateItems->filter(
                fn (ServiceEstimateItem $item) => ! $invoiceGuard->isModernWorkshopService($service)
                    ? $item->estimate_group_id === null || $approvedGroupIds->contains($item->estimate_group_id)
                    : $approvedGroupIds->contains($item->estimate_group_id)
            );

            $linesSubtotal = 0.0;
            $linesDiscount = 0.0;
            $linesTax = 0.0;
            /** @var ServiceEstimateItem $item */
            foreach ($invoiceableItems as $item) {
                $linesSubtotal += round((float) $item->quantity * (float) $item->unit_price, 2);
                $linesDiscount += (float) $item->discount;
                $linesTax += (float) $item->tax_amount;
            }
            $linesSubtotal = round($linesSubtotal, 2);

            // Proportional share of a document-level percent discount.
            $headerDiscount = 0.0;
            if ($locked->discount_type === 'percent') {
                $base = max(0.0, (float) $locked->discount - $linesDiscount);
                $headerDiscount = $linesSubtotal > 0 && $base > 0 ? $linesSubtotal * ($base / max($linesSubtotal, (float) $locked->subtotal)) : 0.0;
            }
            $headerDiscount = round(min($headerDiscount, $linesSubtotal - $linesDiscount), 2);
            $invoiceGrand = round($linesSubtotal - $linesDiscount - $headerDiscount + $linesTax, 2);

            $invoice = Invoice::create([
                'invoice_number' => $this->invoiceService->generateInvoiceNumber(),
                'customer_id' => $locked->customer_id,
                'service_id' => $locked->service_id,
                'vehicle_id' => $locked->vehicle_id,
                'service_estimate_id' => $locked->id,
                'payment_status' => 0,
                'paid_amount' => 0,
                'total_amount' => $linesSubtotal,
                'discount' => round($linesDiscount + $headerDiscount, 2),
                'discount_type' => $locked->discount_type,
                'tax_amount' => round($linesTax, 2),
                'grand_total' => $invoiceGrand,
                'invoice_date' => $overrides['invoice_date'] ?? now()->toDateString(),
                'due_date' => $overrides['due_date'] ?? null,
                'invoice_type' => 'service',
                'notes' => $overrides['notes'] ?? ('Dibuat dari Estimasi '.$locked->estimate_number.' v'.$locked->version.($locked->status === ServiceEstimate::STATUS_PARTIALLY_APPROVED ? ' (pekerjaan disetujui saja)' : '')),
                'created_by' => auth()->id() ?? 1,
                'branch_id' => $locked->branch_id,
            ]);

            /** @var ServiceEstimateItem $item */
            foreach ($invoiceableItems as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->line_total,
                    'discount' => $item->discount,
                    'discount_type' => $item->discount_type,
                ]);
                if ($item->product_id) {
                    StockService::decrement(
                        (int) $item->product_id,
                        (float) $item->quantity,
                        'out',
                        'Invoice #'.$invoice->invoice_number.' dari Estimasi '.$locked->estimate_number,
                        Invoice::class,
                        $invoice->id,
                    );
                }
            }

            $locked->forceFill([
                'status' => ServiceEstimate::STATUS_CONVERTED,
                'converted_at' => now(),
            ])->save();

            // Accounting treatment starts only here — with the INVOICE.
            app(AutoJournalService::class)->journalInvoiceIssued($invoice);

            // Source of truth moves to the invoice after conversion.
            $locked->service()->update(['charge' => $invoice->grand_total]);

            ActivityLog::record('estimate.converted_to_invoice', $locked, "Estimasi {$locked->estimate_number} v{$locked->version} dikonversi ke Invoice {$invoice->invoice_number}", [
                'invoice_id' => $invoice->id,
                'grand_total' => (string) $locked->grand_total,
                'version' => $locked->version,
            ]);

            return $invoice;
        });
    }

    // ------------------------------------------------------------------
    // Reconciliation (estimate vs actual invoice)
    // ------------------------------------------------------------------

    public function approvedCommercialTotal(Service $service): float
    {
        $latestApproved = ServiceEstimate::where('service_id', $service->id)
            ->whereIn('status', [ServiceEstimate::STATUS_APPROVED, ServiceEstimate::STATUS_PARTIALLY_APPROVED])
            ->orderByDesc('version')
            ->first();

        if ($latestApproved === null) {
            return 0.0;
        }

        return $latestApproved->approved_total > 0
            ? (float) $latestApproved->approved_total
            : (float) $latestApproved->grand_total;
    }

    public function reconciliation(Service $service): array
    {
        $latestActive = $this->latestActiveEstimate($service);
        $invoice = Invoice::where('service_id', $service->id)->first();

        $approvedEstimate = $latestActive !== null && $latestActive->status === ServiceEstimate::STATUS_APPROVED
            ? (float) $latestActive->grand_total
            : 0.0;
        $invoiceAmount = $invoice === null ? 0.0 : (float) $invoice->grand_total;

        return [
            'approved_estimate' => $approvedEstimate,
            'invoice_amount' => $invoiceAmount,
            'variance' => round($invoiceAmount - $approvedEstimate, 2),
            'estimate' => $latestActive,
            'invoice' => $invoice,
        ];
    }

    public function latestActiveEstimate(Service $service): ?ServiceEstimate
    {
        return ServiceEstimate::where('service_id', $service->id)
            ->whereIn('status', ServiceEstimate::ACTIVE_STATUSES)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();
    }

    public function defaultTerms(): string
    {
        $custom = Setting::where('key', 'estimate_terms')->value('value');

        return $custom !== null && $custom !== ''
            ? $custom
            : 'Harga estimasi dapat berubah apabila ditemukan kerusakan tambahan setelah pembongkaran/pemeriksaan. Pekerjaan tambahan hanya dilakukan setelah persetujuan pelanggan.';
    }

    protected function flow(): WorkshopFlowService
    {
        return app(WorkshopFlowService::class);
    }
}
