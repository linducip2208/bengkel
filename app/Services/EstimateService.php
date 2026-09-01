<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateItem;
use App\Models\Setting;
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
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createDraft(Service $service, array $data, array $items): ServiceEstimate
    {
        return DB::transaction(function () use ($service, $data, $items) {
            // Serialize concurrent create/update submissions per service.
            Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();

            $existing = ServiceEstimate::where('service_id', $service->id)
                ->where('status', ServiceEstimate::STATUS_DRAFT)
                ->orderByDesc('id')
                ->first();

            if ($existing !== null) {
                return $this->updateDraft($existing, $data, $items);
            }

            return $this->storeNewDraft($service, $data, $items);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function storeNewDraft(Service $service, array $data, array $items): ServiceEstimate
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
     */
    public function updateDraft(ServiceEstimate $estimate, array $data, array $items): ServiceEstimate
    {
        return DB::transaction(function () use ($estimate, $data, $items) {
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

            $locked->forceFill([
                'status' => ServiceEstimate::STATUS_APPROVED,
                'approved_at' => now(),
                'approval_method' => $method,
                'approval_ip' => request()->ip(),
                'approval_user_agent' => substr((string) request()->userAgent(), 0, 255),
                'approved_hash' => $hash,
                'snapshot' => $locked->snapshot ?? $this->buildSnapshot($locked),
            ])->save();
            $this->syncServiceApproved($locked);

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

        // Source of truth before invoicing = approved estimate grand_total.
        $service->update(['charge' => $estimate->grand_total]);
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

            if ($locked->status === ServiceEstimate::STATUS_CONVERTED) {
                $existing = Invoice::where('service_estimate_id', $locked->id)->first();
                abort_if($existing === null, 409, 'Estimasi sudah dikonversi tetapi invoice tidak ditemukan.');

                return $existing; // idempotent
            }

            abort_unless($locked->status === ServiceEstimate::STATUS_APPROVED, 422, 'Hanya estimasi yang disetujui yang bisa dibuatkan invoice.');
            abort_if(Invoice::where('service_estimate_id', $locked->id)->exists(), 409, 'Invoice dari estimasi ini sudah ada.');

            // All estimate money is already server-computed and stored; the
            // invoice copies it verbatim (lines, totals, tax, discount).
            $invoice = Invoice::create([
                'invoice_number' => $this->invoiceService->generateInvoiceNumber(),
                'customer_id' => $locked->customer_id,
                'service_id' => $locked->service_id,
                'vehicle_id' => $locked->vehicle_id,
                'service_estimate_id' => $locked->id,
                'payment_status' => 0,
                'paid_amount' => 0,
                'total_amount' => $locked->subtotal,
                'discount' => $locked->discount,
                'discount_type' => $locked->discount_type,
                'tax_amount' => $locked->tax_amount,
                'grand_total' => $locked->grand_total,
                'invoice_date' => $overrides['invoice_date'] ?? now()->toDateString(),
                'due_date' => $overrides['due_date'] ?? null,
                'invoice_type' => 'service',
                'notes' => $overrides['notes'] ?? ('Dibuat dari Estimasi '.$locked->estimate_number.' v'.$locked->version),
                'created_by' => auth()->id() ?? 1,
                'branch_id' => $locked->branch_id,
            ]);

            foreach ($locked->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->line_total,
                    'discount' => $item->discount,
                    'discount_type' => $item->discount_type,
                ]);
            }

            $locked->forceFill([
                'status' => ServiceEstimate::STATUS_CONVERTED,
                'converted_at' => now(),
            ])->save();

            // Accounting treatment starts only here — with the INVOICE.
            app(AutoJournalService::class)->journalInvoiceIssued($invoice);

            // Source of truth moves to the invoice after conversion.
            $locked->service()->update(['charge' => $locked->grand_total]);

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
            ->where('status', ServiceEstimate::STATUS_APPROVED)
            ->orderByDesc('version')
            ->first();

        return $latestApproved === null ? 0.0 : (float) $latestApproved->grand_total;
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
}
