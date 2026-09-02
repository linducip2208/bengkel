<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
use App\Services\WorkshopFlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkQcController extends Controller
{
    public function __construct(protected WorkshopFlowService $flow) {}

    /** QC page for one service. */
    public function show(Service $service)
    {
        abort_unless((bool) auth()->user()?->can('qc.view'), 403, 'Tidak punya izin melihat QC.');

        $service->load(['customer', 'vehicle.vehicleBrand', 'workPackages.finding', 'workPackages.task', 'workPackages.qcChecks']);

        $awaiting = $service->workPackages->filter(fn (ServiceWorkPackage $p) => in_array($p->status, [ServiceWorkPackage::STATUS_COMPLETED, ServiceWorkPackage::STATUS_QC_FAILED], true));
        $history = $service->workPackages->filter(fn (ServiceWorkPackage $p) => $p->status === ServiceWorkPackage::STATUS_QC_PASSED);

        /** @var view-string $view */
        $view = 'work.qc';

        return view($view, ['service' => $service, 'awaiting' => $awaiting, 'history' => $history]);
    }

    /** PASS / FAIL — FAIL requires a reason; PASS resolves the finding. */
    public function store(Request $request, ServiceWorkPackage $package): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('qc.perform'), 403, 'Tidak punya izin melakukan QC.');

        $validated = $request->validate([
            'result' => 'required|in:passed,failed',
            'notes' => 'nullable|string|max:2000',
        ]);

        $task = $package->task()->first();
        $taskModel = $task;
        \assert($taskModel instanceof ServiceWorkTask);

        $this->flow->submitQc($package, $validated['result'], $validated['notes'] ?? null, $taskModel->id);

        return redirect()
            ->to(route('services.show', $package->service_id).'#tab-qc')
            ->with('success', $validated['result'] === 'passed' ? 'QC lolos — temuan diselesaikan.' : 'QC gagal — pekerjaan dibuka ulang.');
    }
}
