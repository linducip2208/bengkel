<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceWorkPackage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class GatePassEligibilityService
{
    /** @return array{allowed: bool, reasons: array<int, string>} */
    public function eligibility(Service $service): array
    {
        $reasons = [];
        if ($service->cancelled_at) {
            $reasons[] = 'Service sudah dibatalkan.';
        }
        if ((int) $service->workflow_status < 8) {
            $reasons[] = 'Service belum mencapai status Ready.';
        }
        if (! $service->qc_passed_at) {
            $reasons[] = 'QC belum lulus.';
        }

        if (app(WorkshopInvoiceGuard::class)->isModernWorkshopService($service)) {
            /** @var Collection<int, ServiceWorkPackage> $packages */
            $packages = $service->workPackages()
                ->whereIn('status', [
                    ServiceWorkPackage::STATUS_APPROVED,
                    ServiceWorkPackage::STATUS_IN_PROGRESS,
                    ServiceWorkPackage::STATUS_COMPLETED,
                    ServiceWorkPackage::STATUS_QC_FAILED,
                    ServiceWorkPackage::STATUS_QC_PASSED,
                ])->get();
            if ($packages->isEmpty() || $packages->contains(fn ($package) => $package->status !== ServiceWorkPackage::STATUS_QC_PASSED)) {
                $reasons[] = 'Semua pekerjaan yang disetujui harus lulus QC.';
            }
        }

        return ['allowed' => $reasons === [], 'reasons' => $reasons];
    }

    public function assertCanCreate(Service $service, ?int $vehicleId = null): void
    {
        $result = $this->eligibility($service);
        if ($vehicleId !== null && (int) $service->vehicle_id !== $vehicleId) {
            $result['reasons'][] = 'Kendaraan tidak sesuai dengan Service.';
        }
        if ($result['reasons'] !== []) {
            throw ValidationException::withMessages(['service_id' => implode(' ', $result['reasons'])]);
        }
    }

    public function assertCanRelease(Service $service): void
    {
        $this->assertCanCreate($service, (int) $service->vehicle_id);
        /** @var Invoice|null $invoice */
        $invoice = $service->invoice ?: $service->invoice()->latest()->first();
        if (! $invoice) {
            throw ValidationException::withMessages(['gate_pass' => 'Invoice belum dibuat.']);
        }
        if ((int) $invoice->payment_status !== 2) {
            throw ValidationException::withMessages(['gate_pass' => 'Invoice harus lunas sebelum kendaraan keluar.']);
        }
    }

    public function eligibleServices()
    {
        return Service::query()
            ->whereNull('cancelled_at')
            ->where('workflow_status', '>=', 8)
            ->whereNotNull('qc_passed_at')
            ->with('customer')
            ->latest();
    }
}
