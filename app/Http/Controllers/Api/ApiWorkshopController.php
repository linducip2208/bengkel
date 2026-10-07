<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FleetNotificationToken;
use App\Models\Service;
use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkTask;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\WorkshopFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiWorkshopController extends Controller
{
    public function estimates(Request $request): JsonResponse
    {
        $query = ServiceEstimate::query()->with(['service.customer', 'service.vehicle']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($serviceId = $request->get('service_id')) {
            $query->where('service_id', $serviceId);
        }

        $estimates = $query->latest()->paginate($request->integer('per_page', 20));

        return response()->json($estimates);
    }

    public function showEstimate(ServiceEstimate $estimate): JsonResponse
    {
        $estimate->load(['service.customer', 'service.vehicle', 'items', 'groups.items']);

        return response()->json(['data' => $estimate]);
    }

    public function approveEstimate(Request $request, ServiceEstimate $estimate, EstimateService $service): JsonResponse
    {
        $validated = $request->validate([
            'method' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $service->approve($estimate, $validated['method'] ?? 'manual', $validated['reason'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Estimasi disetujui.', 'data' => $result->fresh(['items', 'groups'])]);
    }

    public function rejectEstimate(Request $request, ServiceEstimate $estimate, EstimateService $service): JsonResponse
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:1000']);

        try {
            $result = $service->reject($estimate, $validated['reason'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Estimasi ditolak.', 'data' => $result->fresh()]);
    }

    public function decideEstimateGroups(Request $request, ServiceEstimate $estimate, WorkshopFlowService $flow): JsonResponse
    {
        $validated = $request->validate([
            'decisions' => 'required|array|min:1',
            'decisions.*.group_id' => 'required|integer|exists:service_estimate_groups,id',
            'decisions.*.decision' => 'required|string|in:approved,rejected',
            'method' => 'nullable|string|max:50',
        ]);

        try {
            $result = $flow->submitGroupDecisions($estimate, $validated['decisions'], $validated['method'] ?? 'api');
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Keputusan approval tersimpan.', 'data' => $result->fresh(['groups'])]);
    }

    public function convertEstimate(ServiceEstimate $estimate, EstimateService $service): JsonResponse
    {
        try {
            $invoice = $service->convertToInvoice($estimate);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Estimasi dikonversi ke invoice.', 'data' => $invoice], 201);
    }

    public function workPackages(Request $request): JsonResponse
    {
        $query = ServiceWorkPackage::query()->with(['service', 'items']);

        if ($serviceId = $request->get('service_id')) {
            $query->where('service_id', $serviceId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function showWorkPackage(ServiceWorkPackage $workPackage): JsonResponse
    {
        $workPackage->load(['service.customer', 'service.vehicle', 'items', 'task', 'qcChecks']);

        return response()->json(['data' => $workPackage]);
    }

    public function workTasks(Request $request): JsonResponse
    {
        $query = ServiceWorkTask::query()->with(['service', 'workPackage', 'assignee']);

        if ($serviceId = $request->get('service_id')) {
            $query->where('service_id', $serviceId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function showWorkTask(ServiceWorkTask $workTask): JsonResponse
    {
        $workTask->load(['service', 'workPackage', 'assignee', 'timeEntries']);

        return response()->json(['data' => $workTask]);
    }

    public function transitionTask(ServiceWorkTask $task, string $action, WorkshopFlowService $flow): JsonResponse
    {
        if (! in_array($action, ['start', 'pause', 'finish'], true)) {
            return response()->json(['message' => 'Aksi tidak valid.'], 422);
        }

        try {
            $task = $flow->{$action.'Task'}($task, auth()->id());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Status task diperbarui.', 'data' => $task->fresh()]);
    }

    public function findings(Request $request): JsonResponse
    {
        $query = ServiceFinding::query()->with(['service']);

        if ($serviceId = $request->get('service_id')) {
            $query->where('service_id', $serviceId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function showFinding(ServiceFinding $finding): JsonResponse
    {
        $finding->load(['service.customer', 'service.vehicle']);

        return response()->json(['data' => $finding]);
    }

    public function inspections(Service $service): JsonResponse
    {
        $service->load(['serviceObservationPoints.observationPoint', 'findings']);

        return response()->json(['data' => [
            'service_id' => $service->id,
            'checklist' => $service->serviceObservationPoints,
            'findings' => $service->findings,
        ]]);
    }

    public function submitQc(Request $request, ServiceWorkPackage $workPackage, WorkshopFlowService $flow): JsonResponse
    {
        $validated = $request->validate([
            'result' => 'required|string|in:passed,failed',
            'notes' => 'nullable|string|max:2000',
            'task_id' => 'nullable|integer|exists:service_work_tasks,id',
        ]);

        try {
            $check = $flow->submitQc($workPackage, $validated['result'], $validated['notes'] ?? null, $validated['task_id'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Hasil QC tersimpan.', 'data' => $check], 201);
    }

    public function technicians(Request $request): JsonResponse
    {
        $query = User::query()->role('mekanik')->where('is_active', true);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function branches(Request $request): JsonResponse
    {
        $access = $request->user()->branchAccess();

        $query = Branch::query()->where('is_active', true);

        if (! $access->isUnrestricted()) {
            if ($access->branchIds === []) {
                return response()->json(['data' => []]);
            }
            $query->whereIn('id', $access->branchIds);
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->paginate($request->integer('per_page', 20));

        return response()->json($notifications);
    }

    public function registerDeviceToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:255',
            'platform' => 'nullable|string|in:android,ios,web',
        ]);

        $record = FleetNotificationToken::updateOrCreate(
            ['user_id' => $request->user()->id, 'token' => $validated['token']],
            ['platform' => $validated['platform'] ?? null, 'is_active' => true]
        );

        return response()->json(['message' => 'Token perangkat terdaftar.', 'data' => $record], 201);
    }

    public function unregisterDeviceToken(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => 'required|string|max:255']);

        FleetNotificationToken::where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['message' => 'Token perangkat dihapus.']);
    }

    public function health(): JsonResponse
    {
        $checks = ['database' => 'unreachable', 'cache' => 'unreachable', 'queue' => 'unreachable'];

        try {
            \DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            return response()->json(['status' => 'error', 'checks' => $checks], 503);
        }

        try {
            cache()->put('health:ping', 1, 10);
            $checks['cache'] = cache()->get('health:ping') === 1 ? 'ok' : 'mismatch';
        } catch (\Throwable) {
            // stays unreachable; reported, not fatal for readiness callers
        }

        try {
            $failed = \DB::table('failed_jobs')->count();
            $checks['queue'] = 'ok';
            $checks['failed_jobs'] = $failed;
        } catch (\Throwable) {
            // queue driver without failed_jobs table (e.g. sync): not applicable
            $checks['queue'] = 'n/a';
        }

        $status = in_array('unreachable', [$checks['database'], $checks['cache']], true) ? 503 : 200;

        return response()->json([
            'status' => $status === 200 ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'time' => now()->toIso8601String(),
            'checks' => $checks,
        ], $status);
    }
}
