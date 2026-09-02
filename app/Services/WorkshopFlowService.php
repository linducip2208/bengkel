<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\PartReservation;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateGroup;
use App\Models\ServiceFinding;
use App\Models\ServiceObservationPoint;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkPackageItem;
use App\Models\ServiceWorkQcCheck;
use App\Models\ServiceWorkTask;
use App\Models\ServiceWorkTimeEntry;
use Illuminate\Support\Facades\DB;

/**
 * The Workshop Operating System core:
 * checklist → findings → work packages → estimate (partial approval)
 * → tasks (actual time) → QC → finding resolution.
 *
 * Every mutation is transactional + idempotent. Money is always
 * recomputed server-side from persisted DECIMAL rows.
 */
class WorkshopFlowService
{
    // ------------------------------------------------------------------
    // Checklist → Findings (idempotent generation)
    // ------------------------------------------------------------------

    /**
     * Sync findings from saved checklist results. Repeated saves with the
     * same data never create duplicate findings: for one observation result
     * there is exactly one ACTIVE finding, updated in place.
     */
    public function syncFindingsFromChecklist(Service $service): array
    {
        return DB::transaction(function () use ($service) {
            $created = 0;
            $updated = 0;
            $resolved = 0;

            $results = $service->serviceObservationPoints()->with('observationPoint')->get();

            foreach ($results as $result) {
                /** @var ServiceObservationPoint $result */
                $needsFinding = in_array($result->condition_status, ServiceObservationPoint::FINDING_CONDITIONS, true);

                $active = ServiceFinding::query()
                    ->where('service_id', $service->id)
                    ->where('service_observation_point_id', $result->id)
                    ->whereIn('status', [
                        ServiceFinding::STATUS_OPEN,
                        ServiceFinding::STATUS_WORK_PROPOSED,
                        ServiceFinding::STATUS_APPROVED_FOR_WORK,
                        ServiceFinding::STATUS_IN_PROGRESS,
                    ])
                    ->first();

                if ($needsFinding) {
                    $payload = [
                        'severity' => $result->condition_status,
                        'title' => $result->observationPoint?->observation_point ?? 'Temuan Pemeriksaan',
                        'description' => $result->comment,
                        'measurement_value' => $result->measurement_value,
                        'measurement_unit' => $result->measurement_unit,
                        'updated_by' => auth()->id(),
                    ];

                    if ($active === null) {
                        $finding = ServiceFinding::create(array_merge($payload, [
                            'service_id' => $service->id,
                            'service_observation_point_id' => $result->id,
                            'branch_id' => $service->branch_id,
                            'finding_number' => DocumentNumberService::generate('findings', 'FND', 'Ym', 4),
                            'status' => ServiceFinding::STATUS_OPEN,
                            'created_by' => auth()->id(),
                        ]));

                        ActivityLog::record('finding.created', $finding, "Temuan {$finding->finding_number} [{$finding->severity}] dibuat dari checklist: {$finding->title}", [
                            'severity' => $finding->severity,
                            'service_id' => $service->id,
                            'condition' => $result->condition_status,
                        ]);
                        $created++;
                    } else {
                        // Idempotent update — same primary key, history intact.
                        $active->fill($payload)->save();
                        ActivityLog::record('finding.updated', $active, "Temuan {$active->finding_number} diperbarui dari checklist", [
                            'severity' => $active->severity,
                        ]);
                        $updated++;
                    }
                } elseif ($active !== null && $result->condition_status === ServiceObservationPoint::CONDITION_OK) {
                    // Previously flagged point now reads OK before any approved
                    // work → resolve with audit trail.
                    $this->resolveFinding($active, $service, 'Temuan selesai pada pemeriksaan ulang (checklist OK).');
                    $resolved++;
                }
                // condition == not_checked → leave any existing finding untouched.
            }

            return ['created' => $created, 'updated' => $updated, 'resolved' => $resolved];
        });
    }

    /**
     * Resolve a finding AFTER approved work completed and QC passed
     * (or on re-inspection OK before any approved work).
     */
    public function resolveFinding(ServiceFinding $finding, Service $service, string $reason): ServiceFinding
    {
        if ($finding->status === ServiceFinding::STATUS_RESOLVED) {
            return $finding; // idempotent
        }

        // A finding linked to approved commercial work must never be
        // silently rewritten — it is resolved through QC only.
        $hasApprovedWork = ServiceWorkPackage::where('service_finding_id', $finding->id)
            ->whereIn('status', [ServiceWorkPackage::STATUS_APPROVED, ServiceWorkPackage::STATUS_IN_PROGRESS, ServiceWorkPackage::STATUS_COMPLETED, ServiceWorkPackage::STATUS_QC_PASSED])
            ->exists();

        if ($hasApprovedWork && $reason === 'Temuan selesai pada pemeriksaan ulang (checklist OK).') {
            ActivityLog::record('finding.correction_blocked', $finding, "Upaya resolve manual diblokir untuk {$finding->finding_number} — sudah terhubung pekerjaan disetujui (gunakan QC).");

            return $finding;
        }

        $finding->forceFill([
            'status' => ServiceFinding::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ])->save();

        ActivityLog::record('finding.resolved', $finding, "Temuan {$finding->finding_number} diselesaikan: {$reason}", [
            'old_status' => $finding->getOriginal('status'),
            'new_status' => ServiceFinding::STATUS_RESOLVED,
            'reason' => $reason,
        ]);

        return $finding;
    }

    public function deferFinding(ServiceFinding $finding, ?string $reason = null): ServiceFinding
    {
        if ($finding->status === ServiceFinding::STATUS_DEFERRED) {
            return $finding; // idempotent
        }

        $old = $finding->status;
        $finding->forceFill(['status' => ServiceFinding::STATUS_DEFERRED])->save();

        ActivityLog::record('finding.updated', $finding, "Temuan {$finding->finding_number} ditunda (deferred)", [
            'old_status' => $old,
            'new_status' => ServiceFinding::STATUS_DEFERRED,
            'reason' => $reason,
        ]);

        return $finding;
    }

    // ------------------------------------------------------------------
    // Work Packages
    // ------------------------------------------------------------------

    /**
     * Create or update a DRAFT work package in place (same primary key).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function saveWorkPackage(Service $service, array $data, array $items, ?ServiceWorkPackage $package = null): ServiceWorkPackage
    {
        return DB::transaction(function () use ($service, $data, $items, $package) {
            Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();

            $finding = isset($data['service_finding_id'])
                ? ServiceFinding::query()->whereKey($data['service_finding_id'])->first()
                : null;

            // Idempotent store: a finding already has an editable package →
            // update it in place instead of creating a duplicate. Manual
            // packages (no finding) never dedupe — each POST is a new row.
            if ($package === null && $finding !== null) {
                $package = ServiceWorkPackage::query()
                    ->where('service_id', $service->id)
                    ->whereIn('status', [ServiceWorkPackage::STATUS_DRAFT, ServiceWorkPackage::STATUS_PROPOSED])
                    ->where('service_finding_id', $finding->id)
                    ->first();
            }

            $attributes = [
                'service_id' => $service->id,
                'service_finding_id' => $finding?->id,
                'branch_id' => $service->branch_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'severity_snapshot' => $finding?->severity ?? $package?->severity_snapshot,
                'standard_minutes' => max(0, (int) ($data['standard_minutes'] ?? 0)),
                'updated_by' => auth()->id(),
            ];

            if ($package === null) {
                $package = ServiceWorkPackage::create(array_merge($attributes, [
                    'status' => ServiceWorkPackage::STATUS_DRAFT,
                    'created_by' => auth()->id(),
                ]));

                ActivityLog::record('work_package.created', $package, "Work Package \"{$package->title}\" dibuat".($finding !== null ? " dari {$finding->finding_number}" : ''), [
                    'service_id' => $service->id,
                    'finding_number' => $finding?->finding_number,
                ]);
            } else {
                abort_unless($package->isEditable(), 422, 'Work package sudah diproposikan/disetujui — tidak bisa diubah.');
                $package->update($attributes);

                ActivityLog::record('work_package.updated', $package, "Work Package \"{$package->title}\" diperbarui (draft)");
            }

            $this->persistPackageItems($package, $items);
            $package->refresh();

            // Finding follows: a proposal exists → work_proposed.
            if ($finding !== null && $finding->status === ServiceFinding::STATUS_OPEN) {
                $finding->forceFill(['status' => ServiceFinding::STATUS_WORK_PROPOSED])->save();
            }

            return $package;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function persistPackageItems(ServiceWorkPackage $package, array $items): void
    {
        $package->items()->delete();
        foreach (array_values($items) as $index => $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $type = in_array($item['item_type'] ?? null, [ServiceWorkPackageItem::TYPE_LABOR, ServiceWorkPackageItem::TYPE_PART, ServiceWorkPackageItem::TYPE_OTHER], true)
                ? $item['item_type']
                : ServiceWorkPackageItem::TYPE_OTHER;

            $package->items()->create([
                'item_type' => $type,
                'product_id' => ! empty($item['product_id']) ? (int) $item['product_id'] : null,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'standard_minutes' => max(0, (int) ($item['standard_minutes'] ?? 0)),
                'line_total' => round($quantity * $unitPrice, 2),
                'sort_order' => $index,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Estimate groups (from work packages) + partial approval
    // ------------------------------------------------------------------

    /**
     * Attach an approved/proposed work package to a draft estimate as a
     * group. Idempotent per estimate+package.
     */
    public function addWorkPackageToEstimate(ServiceEstimate $estimate, ServiceWorkPackage $package): ServiceEstimateGroup
    {
        return DB::transaction(function () use ($estimate, $package) {
            $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 422, 'Estimasi sudah dikirim — gunakan revisi untuk mengubahnya.');

            $existing = ServiceEstimateGroup::where('service_estimate_id', $locked->id)
                ->where('service_work_package_id', $package->id)
                ->first();
            if ($existing !== null) {
                return $existing; // idempotent
            }

            $totals = $package->computeTotals();
            $group = ServiceEstimateGroup::create([
                'service_estimate_id' => $locked->id,
                'service_work_package_id' => $package->id,
                'service_finding_id' => $package->service_finding_id,
                'title' => $package->title,
                'severity_snapshot' => $package->severity_snapshot,
                'standard_minutes' => $totals['standard_minutes'],
                'subtotal' => $totals['grand_total'],
                'grand_total' => $totals['grand_total'],
                'customer_decision' => ServiceEstimateGroup::DECISION_PENDING,
                'sort_order' => (int) ServiceEstimateGroup::where('service_estimate_id', $locked->id)->max('sort_order') + 1,
            ]);

            foreach ($package->items as $index => $item) {
                $locked->items()->create([
                    'estimate_group_id' => $group->id,
                    'product_id' => $item->product_id,
                    'item_type' => $item->item_type,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'tax_rate' => null,
                    'tax_amount' => 0,
                    'line_total' => (float) $item->line_total,
                    'sort_order' => $index,
                ]);
            }

            $this->recalculateEstimateFromGroups($locked);

            ActivityLog::record('estimate.group_added', $group, "Grup \"{$group->title}\" ditambahkan ke estimasi {$locked->estimate_number}", [
                'work_package_id' => $package->id,
                'finding_number' => $package->finding?->finding_number,
            ]);

            return $group;
        });
    }

    /** Recompute document header money across ALL estimate items (manual + grouped). */
    public function recalculateEstimateFromGroups(ServiceEstimate $estimate): void
    {
        $items = $estimate->items()->get();
        $lines = 0.0;
        $linesDiscount = 0.0;
        $linesTax = 0.0;

        foreach ($items as $item) {
            $lines += round((float) $item->quantity * (float) $item->unit_price, 2);
            $linesDiscount += (float) $item->discount;
            $linesTax += (float) $item->tax_amount;
        }

        $headerDiscount = 0.0;
        if ($estimate->discount_type === 'percent') {
            $base = (float) $estimate->discount - $linesDiscount;
            $headerDiscount = $lines > 0 && $base > 0 ? $lines * ($base / $lines) : 0.0;
        }

        $estimate->forceFill([
            'subtotal' => round($lines, 2),
            'grand_total' => round($lines - $linesDiscount - $headerDiscount + $linesTax, 2),
        ])->save();
    }

    /**
     * Customer submits per-group decisions. Persists each decision with
     * evidence, derives the estimate decision status + amounts, and creates
     * exactly one work task per approved package (idempotent).
     *
     * @param  array<int, array{group_id: int, decision: string, reason?: string|null}>  $decisions
     */
    public function submitGroupDecisions(ServiceEstimate $estimate, array $decisions, string $method): ServiceEstimate
    {
        return DB::transaction(function () use ($estimate, $decisions, $method) {
            $locked = ServiceEstimate::query()->whereKey($estimate->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [ServiceEstimate::STATUS_APPROVED, ServiceEstimate::STATUS_PARTIALLY_APPROVED, ServiceEstimate::STATUS_REJECTED, ServiceEstimate::STATUS_CONVERTED], true)) {
                return $locked; // decisions already final — idempotent
            }

            abort_unless(in_array($locked->status, [ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL], true), 409, 'Estimasi tidak sedang menunggu persetujuan.');

            $evidence = [
                'decided_at' => now()->toIso8601String(),
                'method' => $method,
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
                'content_hash' => $this->estimates()->contentHash($locked),
                'decisions' => [],
            ];

            foreach ($decisions as $decision) {
                $group = ServiceEstimateGroup::query()
                    ->whereKey((int) $decision['group_id'])
                    ->where('service_estimate_id', $locked->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($group->customer_decision !== ServiceEstimateGroup::DECISION_PENDING) {
                    continue; // already decided — never rewrite history
                }

                $value = $decision['decision'] === ServiceEstimateGroup::DECISION_APPROVED
                    ? ServiceEstimateGroup::DECISION_APPROVED
                    : ServiceEstimateGroup::DECISION_REJECTED;

                $group->forceFill([
                    'customer_decision' => $value,
                    'decision_reason' => $decision['reason'] ?? null,
                    'decided_at' => now(),
                ])->save();

                $evidence['decisions'][] = [
                    'group_id' => $group->id,
                    'title' => $group->title,
                    'decision' => $value,
                    'amount' => (string) $group->grand_total,
                    'reason' => $decision['reason'] ?? null,
                ];

                ActivityLog::record(
                    $value === ServiceEstimateGroup::DECISION_APPROVED ? 'work_package.approved' : 'work_package.rejected',
                    $locked,
                    "Grup \"{$group->title}\" ".($value === ServiceEstimateGroup::DECISION_APPROVED ? 'disetujui' : 'ditolak')." customer (estimasi {$locked->estimate_number} v{$locked->version})",
                    [
                        'group_id' => $group->id,
                        'amount' => (string) $group->grand_total,
                        'reason' => $decision['reason'] ?? null,
                    ]
                );

                if ($group->service_work_package_id !== null) {
                    $package = ServiceWorkPackage::query()->whereKey($group->service_work_package_id)->first();
                    if ($package !== null && in_array($package->status, [ServiceWorkPackage::STATUS_DRAFT, ServiceWorkPackage::STATUS_PROPOSED], true)) {
                        $package->forceFill(['status' => $value === ServiceEstimateGroup::DECISION_APPROVED ? ServiceWorkPackage::STATUS_APPROVED : ServiceWorkPackage::STATUS_REJECTED])->save();
                    }
                }
            }

            // Derive commercial status + amounts (server-side only).
            $groups = ServiceEstimateGroup::where('service_estimate_id', $locked->id)->get();
            $approvedTotal = round((float) $groups->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)->sum('grand_total'), 2);
            $rejectedTotal = round((float) $groups->where('customer_decision', ServiceEstimateGroup::DECISION_REJECTED)->sum('grand_total'), 2);
            $pendingCount = $groups->where('customer_decision', ServiceEstimateGroup::DECISION_PENDING)->count();
            $approvedCount = $groups->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)->count();
            $rejectedCount = $groups->where('customer_decision', ServiceEstimateGroup::DECISION_REJECTED)->count();

            if ($pendingCount > 0) {
                $decisionStatus = ServiceEstimate::DECISION_PENDING;
                $estimateStatus = ServiceEstimate::STATUS_WAITING_APPROVAL;
            } elseif ($rejectedCount === 0) {
                $decisionStatus = ServiceEstimate::DECISION_APPROVED;
                $estimateStatus = ServiceEstimate::STATUS_APPROVED;
            } elseif ($approvedCount === 0) {
                $decisionStatus = ServiceEstimate::DECISION_REJECTED;
                $estimateStatus = ServiceEstimate::STATUS_REJECTED;
            } else {
                $decisionStatus = ServiceEstimate::DECISION_PARTIALLY_APPROVED;
                $estimateStatus = ServiceEstimate::STATUS_PARTIALLY_APPROVED;
            }

            $hash = $this->estimates()->contentHash($locked);
            $locked->forceFill([
                'status' => $estimateStatus,
                'decision_status' => $decisionStatus,
                'approved_total' => $approvedTotal,
                'rejected_total' => $rejectedTotal,
                'decision_evidence' => $evidence,
                'approved_at' => $estimateStatus !== ServiceEstimate::STATUS_WAITING_APPROVAL ? now() : $locked->approved_at,
                'approval_method' => $estimateStatus !== ServiceEstimate::STATUS_WAITING_APPROVAL ? $method : $locked->approval_method,
                'approval_ip' => request()->ip(),
                'approval_user_agent' => substr((string) request()->userAgent(), 0, 255),
                'approved_hash' => $hash,
                'snapshot' => $locked->snapshot ?? $this->estimates()->buildSnapshot($locked),
            ])->save();

            ActivityLog::record(
                $estimateStatus === ServiceEstimate::STATUS_PARTIALLY_APPROVED ? 'estimate.partial_approved' : 'estimate.approved',
                $locked,
                "Keputusan customer estimasi {$locked->estimate_number} v{$locked->version}: {$decisionStatus} (disetujui Rp ".number_format($approvedTotal, 0, ',', '.').')',
                [
                    'decision_status' => $decisionStatus,
                    'approved_total' => (string) $approvedTotal,
                    'rejected_total' => (string) $rejectedTotal,
                    'approved_hash' => $hash,
                    'method' => $method,
                ]
            );

            // Only APPROVED work enters execution — idempotent.
            if (in_array($estimateStatus, [ServiceEstimate::STATUS_APPROVED, ServiceEstimate::STATUS_PARTIALLY_APPROVED], true)) {
                $this->syncServiceApproval($locked);
                $this->createTasksForApprovedGroups($locked);
                $this->reservePartsForApprovedGroups($locked);
            }

            return $locked->refresh();
        });
    }

    protected function syncServiceApproval(ServiceEstimate $estimate): void
    {
        $service = Service::query()->whereKey($estimate->service_id)->lockForUpdate()->first();
        if ($service === null || $service->cancelled_at) {
            return;
        }

        $newerActive = ServiceEstimate::where('service_id', $service->id)
            ->where('version', '>', $estimate->version)
            ->whereIn('status', [ServiceEstimate::STATUS_SENT, ServiceEstimate::STATUS_WAITING_APPROVAL, ServiceEstimate::STATUS_APPROVED, ServiceEstimate::STATUS_PARTIALLY_APPROVED])
            ->exists();
        if ($newerActive) {
            return;
        }

        $service->update([
            'is_approved' => true,
            'approved_at' => $service->approved_at ?? now(),
        ]);
        if ((int) $service->workflow_status === 3) {
            $service->update(['workflow_status' => 4]);
        }

        // Commercial source of truth = APPROVED amount only (never the full
        // estimate total when part was rejected).
        $service->update(['charge' => $estimate->approved_total > 0 ? $estimate->approved_total : $estimate->grand_total]);
    }

    /** One task per approved package — unique constraint + existence check. */
    public function createTasksForApprovedGroups(ServiceEstimate $estimate): int
    {
        $created = 0;
        $groups = ServiceEstimateGroup::where('service_estimate_id', $estimate->id)
            ->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)
            ->whereNotNull('service_work_package_id')
            ->get();

        foreach ($groups as $group) {
            $package = ServiceWorkPackage::query()->whereKey($group->service_work_package_id)->first();
            if ($package === null) {
                continue;
            }

            $exists = ServiceWorkTask::where('service_work_package_id', $package->id)->exists();
            if ($exists) {
                continue; // idempotent retry
            }

            ServiceWorkTask::create([
                'service_id' => $package->service_id,
                'service_work_package_id' => $package->id,
                'branch_id' => $package->branch_id,
                'status' => ServiceWorkTask::STATUS_READY,
                'standard_minutes' => (int) ($group->standard_minutes ?: $package->standard_minutes),
                'created_by' => auth()->id() ?? 1,
            ]);
            $created++;

            ActivityLog::record('work_task.created', $package, "Task dibuat untuk \"{$package->title}\" (estimasi {$estimate->estimate_number} disetujui)");
        }

        return $created;
    }

    /** Reserve parts for approved packages only — idempotent per package+product. */
    public function reservePartsForApprovedGroups(ServiceEstimate $estimate): int
    {
        $reserved = 0;
        $groups = ServiceEstimateGroup::where('service_estimate_id', $estimate->id)
            ->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)
            ->whereNotNull('service_work_package_id')
            ->with('items')
            ->get();

        foreach ($groups as $group) {
            $package = ServiceWorkPackage::query()->whereKey($group->service_work_package_id)->first();
            if ($package === null) {
                continue;
            }

            foreach ($package->items()->where('item_type', ServiceWorkPackageItem::TYPE_PART)->get() as $item) {
                if ($item->product_id === null || (float) $item->quantity <= 0) {
                    continue;
                }

                $exists = PartReservation::where('service_id', $package->service_id)
                    ->where('product_id', $item->product_id)
                    ->where('status', 'reserved')
                    ->where('notes', 'like', 'WP#'.$package->id.'%')
                    ->exists();
                if ($exists) {
                    continue; // idempotent retry
                }

                PartReservation::create([
                    'service_id' => $package->service_id,
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'reserved_by' => auth()->id() ?? 1,
                    'status' => 'reserved',
                    'notes' => 'WP#'.$package->id.' '.$package->title,
                ]);
                $reserved++;
            }
        }

        return $reserved;
    }

    // ------------------------------------------------------------------
    // Work tasks: start / pause / resume / finish (server-side timing)
    // ------------------------------------------------------------------

    public function startTask(ServiceWorkTask $task, ?int $userId = null): ServiceWorkTask
    {
        return DB::transaction(function () use ($task, $userId) {
            $locked = ServiceWorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $userId = $userId ?? auth()->id() ?? 1;

            if ($locked->status === ServiceWorkTask::STATUS_IN_PROGRESS) {
                return $locked; // already running
            }

            abort_unless(in_array($locked->status, [ServiceWorkTask::STATUS_READY, ServiceWorkTask::STATUS_PAUSED, ServiceWorkTask::STATUS_PENDING], true), 409, 'Task tidak bisa dimulai dari status '.$locked->status.'.');

            // Close any dangling open entry defensively.
            ServiceWorkTimeEntry::where('service_work_task_id', $locked->id)->whereNull('ended_at')->update(['ended_at' => now()]);

            ServiceWorkTimeEntry::create([
                'service_work_task_id' => $locked->id,
                'user_id' => $userId,
                'started_at' => now(),
            ]);

            $wasPaused = $locked->status === ServiceWorkTask::STATUS_PAUSED;
            $locked->forceFill([
                'status' => ServiceWorkTask::STATUS_IN_PROGRESS,
                'started_at' => $locked->started_at ?? now(),
            ])->save();

            $package = ServiceWorkPackage::query()->whereKey($locked->service_work_package_id)->first();
            $package?->forceFill(['status' => ServiceWorkPackage::STATUS_IN_PROGRESS])->save();
            if ($package?->service_finding_id !== null) {
                ServiceFinding::query()->whereKey($package->service_finding_id)->where('status', '!=', ServiceFinding::STATUS_RESOLVED)->update(['status' => ServiceFinding::STATUS_IN_PROGRESS]);
            }

            ActivityLog::record($wasPaused ? 'work_task.resumed' : 'work_task.started', $locked, ($wasPaused ? 'Pekerjaan dilanjutkan' : 'Pekerjaan dimulai').": {$package?->title}", [
                'task_id' => $locked->id,
                'user_id' => $userId,
            ]);

            return $locked;
        });
    }

    public function pauseTask(ServiceWorkTask $task, ?int $userId = null): ServiceWorkTask
    {
        return DB::transaction(function () use ($task) {
            $locked = ServiceWorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ServiceWorkTask::STATUS_PAUSED) {
                return $locked; // idempotent
            }

            if ($locked->status !== ServiceWorkTask::STATUS_IN_PROGRESS) {
                return $locked; // not running — pause is a no-op
            }

            $entry = $locked->openEntry();
            if ($entry !== null) {
                $entry->forceFill([
                    'ended_at' => now(),
                    'duration_seconds' => max(0, now()->diffInSeconds($entry->started_at)),
                ])->save();
            }

            $locked->forceFill(['status' => ServiceWorkTask::STATUS_PAUSED])->save();

            $package = ServiceWorkPackage::query()->whereKey($locked->service_work_package_id)->first();
            ActivityLog::record('work_task.paused', $locked, "Pekerjaan dijeda: {$package?->title}", [
                'task_id' => $locked->id,
                'actual_minutes' => $locked->actualMinutes(),
            ]);

            return $locked;
        });
    }

    public function finishTask(ServiceWorkTask $task, ?int $userId = null): ServiceWorkTask
    {
        return DB::transaction(function () use ($task) {
            $locked = ServiceWorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ServiceWorkTask::STATUS_QC_PENDING || $locked->status === ServiceWorkTask::STATUS_COMPLETED) {
                return $locked; // idempotent
            }

            // Close any running timer.
            $entry = $locked->openEntry();
            if ($entry !== null) {
                $entry->forceFill([
                    'ended_at' => now(),
                    'duration_seconds' => max(0, now()->diffInSeconds($entry->started_at)),
                ])->save();
            }

            $locked->forceFill([
                'status' => ServiceWorkTask::STATUS_QC_PENDING,
                'completed_at' => now(),
            ])->save();

            $package = ServiceWorkPackage::query()->whereKey($locked->service_work_package_id)->first();
            $package?->forceFill(['status' => ServiceWorkPackage::STATUS_COMPLETED])->save();

            ActivityLog::record('work_task.completed', $locked, "Pekerjaan selesai, menunggu QC: {$package?->title}", [
                'task_id' => $locked->id,
                'standard_minutes' => $locked->standard_minutes,
                'actual_minutes' => $locked->actualMinutes(),
            ]);

            return $locked;
        });
    }

    // ------------------------------------------------------------------
    // QC closure
    // ------------------------------------------------------------------

    /**
     * Record a QC verdict. PASS resolves the source finding; FAIL reopens
     * the task for rework and keeps the finding unresolved.
     * Retrying the same verdict never duplicates resolution records.
     */
    public function submitQc(ServiceWorkPackage $package, string $result, ?string $notes = null, ?int $taskId = null): ServiceWorkQcCheck
    {
        return DB::transaction(function () use ($package, $result, $notes, $taskId) {
            $locked = ServiceWorkPackage::query()->whereKey($package->id)->lockForUpdate()->firstOrFail();
            $value = $result === ServiceWorkQcCheck::RESULT_PASSED ? ServiceWorkQcCheck::RESULT_PASSED : ServiceWorkQcCheck::RESULT_FAILED;

            abort_unless(in_array($locked->status, [ServiceWorkPackage::STATUS_COMPLETED, ServiceWorkPackage::STATUS_QC_FAILED, ServiceWorkPackage::STATUS_QC_PASSED], true), 409, 'Work package belum selesai dikerjakan.');

            $check = ServiceWorkQcCheck::create([
                'service_work_package_id' => $locked->id,
                'service_work_task_id' => $taskId,
                'result' => $value,
                'notes' => $notes,
                'checked_by' => auth()->id() ?? 1,
                'checked_at' => now(),
            ]);

            $task = ServiceWorkTask::where('service_work_package_id', $locked->id)->first();

            if ($value === ServiceWorkQcCheck::RESULT_PASSED) {
                $locked->forceFill(['status' => ServiceWorkPackage::STATUS_QC_PASSED])->save();
                $task?->forceFill(['status' => ServiceWorkTask::STATUS_QC_PASSED])->save();

                ActivityLog::record('qc.passed', $check, "QC lolos: {$locked->title}", [
                    'work_package_id' => $locked->id,
                    'notes' => $notes,
                ]);

                // The finding resolves ONLY here — after approved work + QC pass.
                if ($locked->service_finding_id !== null) {
                    $finding = ServiceFinding::query()->whereKey($locked->service_finding_id)->first();
                    if ($finding !== null) {
                        $this->resolveFinding($finding, $finding->service()->withoutGlobalScopes()->first() ?? $finding->service, "QC lolos untuk pekerjaan \"{$locked->title}\".");
                    }
                }
            } else {
                // Mandatory reason on FAIL — validated before anything persists.
                if (trim((string) $notes) === '') {
                    abort(422, 'Alasan QC gagal wajib diisi.');
                }

                $locked->forceFill(['status' => ServiceWorkPackage::STATUS_QC_FAILED])->save();
                if ($task !== null) {
                    // Reopen for rework — the timer may start again.
                    $task->forceFill(['status' => ServiceWorkTask::STATUS_READY, 'completed_at' => null])->save();
                }

                ActivityLog::record('qc.failed', $check, "QC gagal: {$locked->title} — {$notes}", [
                    'work_package_id' => $locked->id,
                    'notes' => $notes,
                ]);
            }

            return $check;
        });
    }

    // ------------------------------------------------------------------
    // Progress helpers (checklist completeness)
    // ------------------------------------------------------------------

    public function checklistProgress(Service $service): array
    {
        $results = $service->serviceObservationPoints()->get();
        $total = $results->count();
        $checked = $results->where('condition_status', '!=', ServiceObservationPoint::CONDITION_NOT_CHECKED)->count();

        $byStatus = [
            ServiceObservationPoint::CONDITION_NOT_CHECKED => 0,
            ServiceObservationPoint::CONDITION_OK => 0,
            ServiceObservationPoint::CONDITION_ATTENTION => 0,
            ServiceObservationPoint::CONDITION_REPAIR_REQUIRED => 0,
            ServiceObservationPoint::CONDITION_CRITICAL => 0,
        ];
        foreach ($results as $r) {
            $byStatus[$r->condition_status] = ($byStatus[$r->condition_status] ?? 0) + 1;
        }

        return [
            'checked_count' => $checked,
            'total_points' => $total,
            'percentage' => $total > 0 ? (int) round($checked / $total * 100) : 0,
            'critical_count' => $byStatus[ServiceObservationPoint::CONDITION_CRITICAL],
            'by_status' => $byStatus,
        ];
    }

    protected function estimates(): EstimateService
    {
        return app(EstimateService::class);
    }
}
