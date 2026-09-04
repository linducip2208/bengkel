<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\GatePass;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceEstimateGroup;
use App\Models\ServiceFinding;
use App\Models\ServiceObservationPoint;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
use Illuminate\Support\Collection;

/** Read-only projection of persisted workshop state. */
class WorkshopProgressService
{
    public const COMPLETED = 'completed';

    public const CURRENT = 'current';

    public const PENDING = 'pending';

    public const WARNING = 'warning';

    public const BLOCKED = 'blocked';

    private const ACTIONABLE_FINDING_STATUSES = [ServiceFinding::STATUS_OPEN, ServiceFinding::STATUS_WORK_PROPOSED, ServiceFinding::STATUS_APPROVED_FOR_WORK, ServiceFinding::STATUS_IN_PROGRESS];

    public function calculate(Service $service): array
    {
        $service->loadMissing(['serviceObservationPoints', 'findings.workPackages', 'workPackages.task', 'workPackages.qcChecks', 'estimates.groups', 'invoice.paymentRecords']);
        $points = $service->serviceObservationPoints;
        $findings = $service->findings;
        $packages = $service->workPackages;
        /** @var ServiceEstimate|null $estimate */
        $estimate = $service->estimates->sortByDesc('version')->first();
        $active = $findings->filter(fn (ServiceFinding $f) => $f->isActive());
        $actionable = $findings->whereIn('status', self::ACTIONABLE_FINDING_STATUSES);
        $missing = $actionable->filter(fn (ServiceFinding $f) => $f->workPackages->whereNotIn('status', [ServiceWorkPackage::STATUS_REJECTED, ServiceWorkPackage::STATUS_CANCELLED])->isEmpty());
        $approvedIds = $estimate instanceof ServiceEstimate
            ? $estimate->groups->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)->pluck('service_work_package_id')->filter()
            : collect();
        $executable = $packages->whereIn('id', $approvedIds);
        $tasks = $executable->pluck('task')->filter();
        $finished = $tasks->whereIn('status', [ServiceWorkTask::STATUS_COMPLETED, ServiceWorkTask::STATUS_QC_PENDING, ServiceWorkTask::STATUS_QC_PASSED, ServiceWorkTask::STATUS_QC_FAILED]);
        $qcPassed = $executable->where('status', ServiceWorkPackage::STATUS_QC_PASSED);
        $invoice = $service->invoice;
        $paid = $invoice ? max((float) $invoice->paid_amount, (float) $invoice->paymentRecords->sum('amount')) : 0.0;
        $paidInFull = $invoice && $paid >= (float) $invoice->grand_total - 0.009;
        $released = $service->released_at !== null || GatePass::query()->where('service_id', $service->id)->where('status', 'out')->exists();
        $checklist = $this->checklist($points);
        $hasBooking = Booking::query()->where('service_id', $service->id)->exists();
        $qcReady = $executable->isNotEmpty() && $finished->count() === $tasks->count();
        $steps = [
            'source' => $this->step($hasBooking ? 'Booking' : 'Walk-In', self::COMPLETED, $hasBooking ? 'Booking terhubung' : 'Service dibuat sebagai Walk-In', null, $hasBooking ? 'booking' : 'walk-in'),
            'check_in' => $this->step('Check-In', $this->checkedIn($service) ? self::COMPLETED : self::CURRENT, $this->checkedIn($service) ? 'Kendaraan sudah diterima' : 'Kendaraan belum check-in', 'check-in'),
            'checklist' => $this->step('Checklist / Inspeksi', $checklist['complete'] ? self::COMPLETED : ($checklist['started'] ? self::WARNING : self::PENDING), $checklist['checked_count'].'/'.$checklist['total_points'].' poin diperiksa', 'checklist', $checklist),
            'findings' => $this->step('Temuan / Finding', $this->findingState($checklist, $actionable), $active->count().' temuan aktif', 'findings', ['active_count' => $active->count(), 'actionable_count' => $actionable->count()]),
            'work_package' => $this->step('Rencana Pekerjaan', $this->packageState($checklist, $actionable, $missing), $missing->count().' temuan belum punya rencana', 'work', ['draft' => $packages->where('status', ServiceWorkPackage::STATUS_DRAFT)->count(), 'proposed' => $packages->where('status', ServiceWorkPackage::STATUS_PROPOSED)->count(), 'approved' => $approvedIds->count()]),
            'estimate' => $this->step('Estimasi', $this->estimateState($estimate), $estimate ? $estimate->statusLabel().' · v'.$estimate->version : 'Belum ada estimasi', 'estimate', ['estimate' => $estimate]),
            'approval' => $this->step('Approval Customer', $this->approvalState($estimate), $this->approvalDetail($estimate), 'estimate', $this->approvalCounts($estimate)),
            'work' => $this->step('Pekerjaan', $this->workState($executable, $tasks, $finished), $tasks->count().' task · '.$finished->count().' selesai', 'work-execution', ['tasks' => $tasks->count()]),
            'qc' => $this->step('QC', $this->qcState($executable, $qcPassed, $qcReady), $qcPassed->count().'/'.$executable->count().' package lulus QC', 'qc', ['passed' => $qcPassed->count(), 'total' => $executable->count()]),
            'invoice' => $this->step('Invoice', $invoice ? self::COMPLETED : self::PENDING, $invoice ? 'Invoice dibuat' : 'Belum dibuat', 'invoice', ['invoice' => $invoice, 'paid' => $paid]),
            'payment' => $this->step('Payment', ! $invoice ? self::BLOCKED : ($paidInFull ? self::COMPLETED : self::CURRENT), ! $invoice ? 'Menunggu invoice' : ($paidInFull ? 'Lunas' : 'Menunggu pelunasan'), 'invoice', ['paid' => $paid, 'remaining' => $invoice ? max((float) $invoice->grand_total - $paid, 0) : null]),
            'release' => $this->step('Gate Pass / Release', $released ? self::COMPLETED : ($paidInFull && $qcPassed->count() === $executable->count() && $executable->isNotEmpty() ? self::CURRENT : self::BLOCKED), $released ? 'Kendaraan sudah keluar' : 'Menunggu QC dan pembayaran', 'gate-pass'),
            'completed' => $this->step('Completed', $released && $paidInFull && $service->completed_at ? self::COMPLETED : self::PENDING, $service->completed_at ? 'Service selesai' : 'Belum selesai', null),
        ];

        return ['steps' => $steps, 'current_step' => $this->currentStep($steps), 'next_action' => $this->nextAction($steps), 'checklist' => $checklist];
    }

    public function stageLabel(Service $service): string
    {
        $p = $this->calculate($service);

        return $p['steps'][$p['current_step']]['label'] ?? 'Booking';
    }

    public function nextActionFor(Service $service): array
    {
        return $this->calculate($service)['next_action'];
    }

    private function checklist(Collection $points): array
    {
        $total = $points->count();
        $checked = $points->where('condition_status', '!=', ServiceObservationPoint::CONDITION_NOT_CHECKED)->count();

        return ['total_points' => $total, 'checked_count' => $checked, 'started' => $checked > 0, 'complete' => $total > 0 && $checked === $total, 'percentage' => $total ? (int) round($checked / $total * 100) : 0];
    }

    private function checkedIn(Service $s): bool
    {
        return $s->checked_in_at !== null || (int) $s->workflow_status >= 1;
    }

    private function findingState(array $c, Collection $a): string
    {
        return ! $c['complete'] ? self::PENDING : ($a->isEmpty() ? self::COMPLETED : self::CURRENT);
    }

    private function packageState(array $c, Collection $a, Collection $m): string
    {
        return ! $c['complete'] ? self::BLOCKED : ($a->isEmpty() ? self::COMPLETED : ($m->isEmpty() ? self::COMPLETED : self::CURRENT));
    }

    private function estimateState(?ServiceEstimate $e): string
    {
        return ! $e ? self::PENDING : ($e->status === ServiceEstimate::STATUS_DRAFT ? self::CURRENT : self::COMPLETED);
    }

    private function approvalState(?ServiceEstimate $e): string
    {
        if (! $e || $e->status === ServiceEstimate::STATUS_DRAFT) {
            return self::BLOCKED;
        } if (in_array($e->status, [ServiceEstimate::STATUS_APPROVED, ServiceEstimate::STATUS_CONVERTED], true)) {
            return self::COMPLETED;
        } if (in_array($e->status, [ServiceEstimate::STATUS_PARTIALLY_APPROVED, ServiceEstimate::STATUS_REJECTED], true)) {
            return self::WARNING;
        }

        return self::CURRENT;
    }

    private function approvalDetail(?ServiceEstimate $e): string
    {
        return $e ? (ServiceEstimate::STATUS_LABELS[$e->status] ?? $e->status) : 'Buat estimasi melalui Rencana Pekerjaan';
    }

    private function approvalCounts(?ServiceEstimate $e): array
    {
        $g = $e ? $e->groups : collect();

        return ['approved' => $g->where('customer_decision', ServiceEstimateGroup::DECISION_APPROVED)->count(), 'rejected' => $g->where('customer_decision', ServiceEstimateGroup::DECISION_REJECTED)->count(), 'pending' => $g->where('customer_decision', ServiceEstimateGroup::DECISION_PENDING)->count()];
    }

    private function workState(Collection $p, Collection $t, Collection $f): string
    {
        if ($p->isEmpty()) {
            return self::BLOCKED;
        } if ($t->count() < $p->count()) {
            return self::WARNING;
        }

        return $f->count() === $t->count() ? self::COMPLETED : self::CURRENT;
    }

    private function qcState(Collection $p, Collection $passed, bool $ready): string
    {
        if ($p->isEmpty()) {
            return self::BLOCKED;
        } if ($p->where('status', ServiceWorkPackage::STATUS_QC_FAILED)->isNotEmpty()) {
            return self::WARNING;
        } if ($passed->count() === $p->count()) {
            return self::COMPLETED;
        }

        return $ready ? self::CURRENT : self::BLOCKED;
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

        return 'completed';
    }

    private function nextAction(array $steps): array
    {
        $key = $this->currentStep($steps);
        $a = ['check_in' => ['label' => 'Lanjutkan Check-In', 'target' => 'tab-jobcard'], 'checklist' => ['label' => 'Lanjutkan Checklist', 'target' => 'tab-checklist'], 'findings' => ['label' => 'Lihat Temuan', 'target' => 'tab-findings'], 'work_package' => ['label' => 'Buat Rencana Pekerjaan', 'target' => 'tab-work'], 'estimate' => ['label' => 'Lanjutkan Estimasi', 'target' => 'tab-estimate'], 'approval' => ['label' => 'Lihat Approval Customer', 'target' => 'tab-estimate'], 'work' => ['label' => 'Mulai Pekerjaan', 'target' => 'tab-work-execution'], 'qc' => ['label' => 'Lakukan QC', 'target' => 'tab-qc'], 'invoice' => ['label' => 'Buat Invoice', 'target' => 'tab-invoice'], 'payment' => ['label' => 'Catat Pembayaran', 'target' => 'tab-invoice'], 'release' => ['label' => 'Buat / Proses Gate Pass', 'target' => 'tab-gate-pass'], 'completed' => ['label' => 'Lihat Riwayat', 'target' => 'tab-history']];

        return ['key' => $key, ...($a[$key] ?? ['label' => 'Lihat Detail', 'target' => 'tab-info'])];
    }
}
