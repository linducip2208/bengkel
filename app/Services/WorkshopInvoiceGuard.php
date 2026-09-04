<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateGroup;
use App\Models\ServiceWorkPackage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Server-side eligibility boundary for modern workshop service invoices.
 *
 * Generic invoices remain available for POS/sales and explicitly legacy
 * services. Modern workshop services must be converted from an approved
 * estimate after approved work has passed QC.
 */
class WorkshopInvoiceGuard
{
    public function isModernWorkshopService(Service $service): bool
    {
        return $service->serviceObservationPoints()->exists()
            || $service->findings()->exists()
            || $service->workPackages()->exists();
    }

    public function canCreateServiceInvoice(Service $service): bool
    {
        return $this->eligibility($service)['allowed'];
    }

    public function assertCanCreateServiceInvoice(Service $service): void
    {
        $eligibility = $this->eligibility($service);

        if (! $eligibility['allowed']) {
            throw ValidationException::withMessages([
                'service_id' => implode(' ', $eligibility['reasons']),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function eligibility(Service $service, ?ServiceEstimate $estimate = null): array
    {
        $modern = $this->isModernWorkshopService($service);
        $reasons = [];
        $existing = Invoice::query()->where('service_id', $service->id)->first();

        if ($service->cancelled_at) {
            $reasons[] = 'Service sudah dibatalkan.';
        }

        if (! $modern) {
            if ($existing) {
                $reasons[] = 'Service sudah memiliki invoice.';
            }

            return [
                'allowed' => $reasons === [],
                'modern' => false,
                'legacy' => true,
                'estimate' => null,
                'approved_packages' => collect(),
                'existing_invoice' => $existing,
                'reasons' => $reasons,
            ];
        }

        /** @var ServiceEstimate|null $latestEstimate */
        $latestEstimate = $service->estimates()
            ->whereIn('status', [
                ServiceEstimate::STATUS_APPROVED,
                ServiceEstimate::STATUS_PARTIALLY_APPROVED,
                ServiceEstimate::STATUS_CONVERTED,
            ])->orderByDesc('version')->first();
        $estimate ??= $latestEstimate;

        if (! $estimate) {
            $reasons[] = 'Belum ada estimasi yang disetujui customer.';
        }

        if ($estimate?->status === ServiceEstimate::STATUS_CONVERTED && $existing) {
            return [
                'allowed' => true,
                'modern' => true,
                'legacy' => false,
                'estimate' => $estimate,
                'approved_packages' => collect(),
                'existing_invoice' => $existing,
                'reasons' => [],
            ];
        }

        if ($estimate && ! in_array($estimate->status, [
            ServiceEstimate::STATUS_APPROVED,
            ServiceEstimate::STATUS_PARTIALLY_APPROVED,
        ], true)) {
            $reasons[] = 'Estimasi belum berstatus disetujui.';
        }

        /** @var Collection<int, ServiceEstimateGroup> $groups */
        $groups = $estimate ? $estimate->groups : collect();
        $approvedGroupIds = $groups
            ->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)
            ->pluck('service_work_package_id')
            ->filter();
        /** @var Collection<int, ServiceWorkPackage> $packages */
        $packages = $service->workPackages()
            ->whereIn('id', $approvedGroupIds)
            ->get();

        if ($packages->isEmpty()) {
            $reasons[] = 'Belum ada Work Package yang disetujui customer.';
        }

        $notFinished = $packages->whereNotIn('status', [
            ServiceWorkPackage::STATUS_COMPLETED,
            ServiceWorkPackage::STATUS_QC_PASSED,
        ]);
        if ($notFinished->isNotEmpty()) {
            $reasons[] = 'Semua pekerjaan yang disetujui harus selesai sebelum invoice.';
        }

        $notQcPassed = $packages->where('status', '!=', ServiceWorkPackage::STATUS_QC_PASSED);
        if ($notQcPassed->isNotEmpty()) {
            $reasons[] = 'Semua pekerjaan yang disetujui harus lulus QC sebelum invoice.';
        }

        if ($existing) {
            $reasons[] = 'Service sudah memiliki invoice aktif.';
        }

        return [
            'allowed' => $reasons === [],
            'modern' => true,
            'legacy' => false,
            'estimate' => $estimate,
            'approved_packages' => $packages,
            'existing_invoice' => $existing,
            'reasons' => $reasons,
        ];
    }
}
