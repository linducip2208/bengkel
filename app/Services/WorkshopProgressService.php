<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateGroup;
use App\Models\ServiceFinding;
use App\Models\ServiceObservationPoint;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
use Illuminate\Support\Collection;

/**
 * Read-only projection of the canonical workshop journey.
 *
 * This class deliberately never persists state. Legacy workflow_status is
 * used only as a check-in signal; all later stages come from their records.
 */
class WorkshopProgressService
{
    public const COMPLETED = 'completed';

    public const CURRENT = 'current';

    public const PENDING = 'pending';

    public const WARNING = 'warning';

    public const BLOCKED = 'blocked';

    public function calculate(Service $service): array
    {
        $service->loadMissing([
            'serviceObservationPoints',
            'findings.workPackages',
            'workPackages.task',
            'workPackages.qcChecks',
            'estimates.groups',
            'invoice.paymentRecords',
        ]);

        $points = $service->serviceObservationPoints;
        $findings = $service->findings;
        $packages = $service->workPackages;
        /** @var ServiceEstimate|null $estimate */
        $estimate = $service->estimates
            ->whereIn('status', ServiceEstimate::ACTIVE_STATUSES)
            ->sortByDesc('version')
            ->first() ?? $service->estimates->sortByDesc('version')->first();
        $activeFindings = $findings->filter(fn (ServiceFinding $finding) => $finding->isActive());
        $actionableFindings = $activeFindings->filter(fn (ServiceFinding $finding) => $finding->isActive());
        $missingPackages = $actionableFindings->filter(fn (ServiceFinding $finding) => $finding->workPackages->whereNotIn('status', [
            ServiceWorkPackage::STATUS_REJECTED,
            ServiceWorkPackage::STATUS_CANCELLED,
        ])->isEmpty());
        $approvedPackages = $packages->where('status', ServiceWorkPackage::STATUS_APPROVED);
        $executablePackages = $packages->whereIn('status', [
            ServiceWorkPackage::STATUS_APPROVED,
            ServiceWorkPackage::STATUS_IN_PROGRESS,
            ServiceWorkPackage::STATUS_COMPLETED,
            ServiceWorkPackage::STATUS_QC_FAILED,
            ServiceWorkPackage::STATUS_QC_PASSED,
        ]);
        $tasks = $executablePackages->pluck('task')->filter();
        $completedTasks = $tasks->filter(fn (ServiceWorkTask $task) => in_array($task->status, [
            ServiceWorkTask::STATUS_COMPLETED,
            ServiceWorkTask::STATUS_QC_PENDING,
            ServiceWorkTask::STATUS_QC_PASSED,
            ServiceWorkTask::STATUS_QC_FAILED,
        ], true));
        $qcPassed = $executablePackages->filter(fn (ServiceWorkPackage $package) => $package->status === ServiceWorkPackage::STATUS_QC_PASSED);
        $invoice = $service->invoice;
        $paid = (float) ($invoice?->paymentRecords?->sum('amount') ?? 0);
        $checklist = $this->checklist($points);
        $hasBooking = Booking::query()->where('service_id', $service->id)->exists();

        $steps = [
            'source' => $this->step($hasBooking ? 'Booking' : 'Walk-In', $hasBooking ? self::COMPLETED : self::COMPLETED, $hasBooking ? 'Booking terhubung' : 'Service dibuat sebagai Walk-In', null, $hasBooking ? 'booking' : 'walk-in'),
            'check_in' => $this->step('Check-In', $this->checkedIn($service) ? self::COMPLETED : self::CURRENT, $this->checkedIn($service) ? 'Kendaraan sudah diterima' : 'Kendaraan belum check-in', 'check-in'),
            'checklist' => $this->step('Checklist / Inspeksi', $checklist['complete'] ? self::COMPLETED : ($checklist['started'] ? self::WARNING : self::PENDING), $checklist['checked_count'].'/'.$checklist['total_points'].' poin diperiksa', 'checklist', $checklist),
            'findings' => $this->step('Temuan / Finding', $this->findingState($checklist, $activeFindings, $actionableFindings), $activeFindings->count().' temuan aktif', 'findings', ['active_count' => $activeFindings->count(), 'actionable_count' => $actionableFindings->count()]),
            'work_package' => $this->step('Rencana Pekerjaan', $this->packageState($checklist, $actionableFindings, $missingPackages, $packages), $missingPackages->count().' temuan belum punya rencana', 'work-package', ['draft' => $packages->where('status', ServiceWorkPackage::STATUS_DRAFT)->count(), 'proposed' => $packages->where('status', ServiceWorkPackage::STATUS_PROPOSED)->count(), 'approved' => $approvedPackages->count()]),
            'estimate' => $this->step('Estimasi', $this->estimateState($estimate), $estimate ? $estimate->statusLabel().' · v'.$estimate->version : 'Belum ada estimasi', 'estimate', ['estimate' => $estimate]),
            'approval' => $this->step('Approval Customer', $this->approvalState($estimate), $this->approvalDetail($estimate), 'approval', $this->approvalCounts($estimate)),
            'work' => $this->step('Pekerjaan', $this->workState($executablePackages, $tasks, $completedTasks), $tasks->count().' task · '.$completedTasks->count().' selesai', 'work', ['tasks' => $tasks->count()]),
            'qc' => $this->step('QC', $this->qcState($executablePackages, $qcPassed), $qcPassed->count().'/'.$executablePackages->count().' package lolos QC', 'qc', ['passed' => $qcPassed->count(), 'total' => $executablePackages->count()]),
            'invoice' => $this->step('Invoice', $invoice ? ($paid >= (float) $invoice->grand_total ? self::COMPLETED : self::CURRENT) : self::PENDING, $invoice ? ($paid >= (float) $invoice->grand_total ? 'Lunas' : 'Invoice dibuat · belum lunas') : 'Belum dibuat', 'invoice', ['invoice' => $invoice, 'paid' => $paid]),
        ];

        return [
            'steps' => $steps,
            'current_step' => $this->currentStep($steps),
            'next_action' => $this->nextAction($steps),
            'checklist' => $checklist,
        ];
    }

    public function stageLabel(Service $service): string
    {
        $progress = $this->calculate($service);
        $step = $progress['steps'][$progress['current_step']] ?? $progress['steps']['invoice'];

        return $step['label'];
    }

    public function nextActionFor(Service $service): array
    {
        return $this->calculate($service)['next_action'];
    }

    private function checklist(Collection $points): array
    {
        $total = $points->count();
        $checked = $points->where('condition_status', '!=', ServiceObservationPoint::CONDITION_NOT_CHECKED)->count();

        return [
            'total_points' => $total,
            'checked_count' => $checked,
            'started' => $checked > 0,
            'complete' => $total > 0 && $checked === $total,
            'percentage' => $total > 0 ? (int) round($checked / $total * 100) : 0,
        ];
    }

    private function checkedIn(Service $service): bool
    {
        return $service->checked_in_at !== null || (int) $service->workflow_status >= 1;
    }

    private function findingState(array $checklist, Collection $active, Collection $actionable): string
    {
        if (! $checklist['complete']) {
            return self::PENDING;
        }
        if ($actionable->isEmpty()) {
            return self::COMPLETED;
        }

        return $active->count() >= $actionable->count() ? self::COMPLETED : self::WARNING;
    }

    private function packageState(array $checklist, Collection $actionable, Collection $missing, Collection $packages): string
    {
        if (! $checklist['complete']) {
            return self::BLOCKED;
        }
        if ($actionable->isEmpty()) {
            return self::COMPLETED;
        }
        if ($missing->isNotEmpty()) {
            return $packages->isEmpty() ? self::PENDING : self::WARNING;
        }

        return self::COMPLETED;
    }

    private function estimateState(?ServiceEstimate $estimate): string
    {
        if ($estimate === null) {
            return self::PENDING;
        }

        return $estimate->status === ServiceEstimate::STATUS_DRAFT ? self::WARNING : self::COMPLETED;
    }

    private function approvalState(?ServiceEstimate $estimate): string
    {
        if ($estimate === null || $estimate->status === ServiceEstimate::STATUS_DRAFT) {
            return self::BLOCKED;
        }
        if ($estimate->status === ServiceEstimate::STATUS_APPROVED) {
            return self::COMPLETED;
        }
        if ($estimate->status === ServiceEstimate::STATUS_PARTIALLY_APPROVED) {
            return self::WARNING;
        }
        if ($estimate->status === ServiceEstimate::STATUS_REJECTED) {
            return self::WARNING;
        }
        if ($estimate->status === ServiceEstimate::STATUS_CONVERTED) {
            return self::COMPLETED;
        }

        return self::CURRENT;
    }

    private function approvalDetail(?ServiceEstimate $estimate): string
    {
        if (! $estimate) {
            return 'Buat estimasi melalui Rencana Pekerjaan';
        }

        return ServiceEstimate::STATUS_LABELS[$estimate->status] ?? $estimate->status;
    }

    private function approvalCounts(?ServiceEstimate $estimate): array
    {
        $groups = $estimate !== null ? $estimate->groups : collect();

        return [
            'approved' => $groups->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)->count(),
            'rejected' => $groups->where('customer_decision', ServiceEstimateGroup::DECISION_REJECTED)->count(),
            'pending' => $groups->where('customer_decision', ServiceEstimateGroup::DECISION_PENDING)->count(),
        ];
    }

    private function workState(Collection $executablePackages, Collection $tasks, Collection $completedTasks): string
    {
        if ($executablePackages->isEmpty()) {
            return self::BLOCKED;
        }
        if ($tasks->count() < $executablePackages->count()) {
            return self::WARNING;
        }
        if ($completedTasks->count() === $tasks->count()) {
            return self::COMPLETED;
        }

        return self::CURRENT;
    }

    private function qcState(Collection $packages, Collection $passed): string
    {
        if ($packages->isEmpty()) {
            return self::BLOCKED;
        }
        if ($packages->where('status', ServiceWorkPackage::STATUS_QC_FAILED)->isNotEmpty()) {
            return self::WARNING;
        }
        if ($passed->count() === $packages->count()) {
            return self::COMPLETED;
        }
        if ($packages->whereIn('status', [ServiceWorkPackage::STATUS_COMPLETED, ServiceWorkPackage::STATUS_QC_PASSED])->isNotEmpty()) {
            return self::CURRENT;
        }

        return self::BLOCKED;
    }

    private function step(string $label, string $state, string $detail, ?string $anchor, mixed $data = null): array
    {
        return compact('label', 'state', 'detail', 'anchor', 'data');
    }

    private function currentStep(array $steps): string
    {
        foreach ($steps as $key => $step) {
            if (in_array($step['state'], [self::CURRENT, self::WARNING, self::BLOCKED, self::PENDING], true)) {
                return $key;
            }
        }

        return 'invoice';
    }

    private function nextAction(array $steps): array
    {
        $key = $this->currentStep($steps);
        $actions = [
            'check_in' => ['label' => 'Lanjutkan Check-In', 'target' => 'tab-jobcard'],
            'checklist' => ['label' => 'Lanjutkan Checklist', 'target' => 'tab-checklist'],
            'findings' => ['label' => 'Lihat Temuan', 'target' => 'tab-findings'],
            'work_package' => ['label' => 'Buat Rencana Pekerjaan', 'target' => 'tab-work'],
            'estimate' => ['label' => 'Lanjutkan Estimasi', 'target' => 'tab-estimate'],
            'approval' => ['label' => 'Lihat Approval', 'target' => 'tab-estimate'],
            'work' => ['label' => 'Mulai Pekerjaan', 'target' => 'tab-work-execution'],
            'qc' => ['label' => 'Lakukan QC', 'target' => 'tab-qc'],
            'invoice' => ['label' => 'Buat Invoice', 'target' => 'tab-invoice'],
        ];

        return ['key' => $key, ...($actions[$key] ?? ['label' => 'Lihat Detail', 'target' => 'tab-info'])];
    }
}
