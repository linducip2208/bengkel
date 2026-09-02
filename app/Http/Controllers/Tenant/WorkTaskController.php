<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ServiceWorkTask;
use App\Services\WorkshopFlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkTaskController extends Controller
{
    public function __construct(protected WorkshopFlowService $flow) {}

    /** START (or RESUME) — server owns the timer. */
    public function start(Request $request, ServiceWorkTask $task): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('work-tasks.start'), 403, 'Tidak punya izin memulai pekerjaan.');

        $this->flow->startTask($task);

        return redirect()
            ->to(route('services.show', $task->service_id).'#tab-work')
            ->with('success', 'Pekerjaan dimulai.');
    }

    public function pause(Request $request, ServiceWorkTask $task): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('work-tasks.start'), 403, 'Tidak punya izin mengontrol pekerjaan.');

        $this->flow->pauseTask($task);

        return redirect()
            ->to(route('services.show', $task->service_id).'#tab-work')
            ->with('success', 'Pekerjaan dijeda.');
    }

    /** FINISH — moves the package into QC pending. */
    public function finish(Request $request, ServiceWorkTask $task): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('work-tasks.finish'), 403, 'Tidak punya izin menyelesaikan pekerjaan.');

        $this->flow->finishTask($task);

        return redirect()
            ->to(route('services.show', $task->service_id).'#tab-qc')
            ->with('success', 'Pekerjaan selesai — menunggu QC.');
    }

    /** Assign a mechanic (idempotent). */
    public function assign(Request $request, ServiceWorkTask $task): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('work-tasks.start'), 403, 'Tidak punya izin menugaskan pekerjaan.');

        $validated = $request->validate([
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $task->forceFill(['assigned_to' => $validated['assigned_to'] ?? null])->save();

        return redirect()
            ->to(route('services.show', $task->service_id).'#tab-work')
            ->with('success', 'Teknisi ditugaskan.');
    }

    public function show(ServiceWorkTask $task)
    {
        abort_unless((bool) auth()->user()?->can('work-tasks.view'), 403, 'Tidak punya izin melihat pekerjaan.');

        $task->load(['workPackage.items.product', 'workPackage.finding', 'assignee', 'timeEntries.user']);

        /** @var view-string $view */
        $view = 'work.task';

        return view($view, ['task' => $task->load('service.customer', 'service.vehicle')]);
    }
}
