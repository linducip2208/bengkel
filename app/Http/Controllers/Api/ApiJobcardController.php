<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiJobcardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with(['customer', 'vehicle', 'jobcardDetail']);

        if ($request->filled('status')) {
            $query->where('done_status', $request->get('status'));
        }

        if ($request->filled('workflow_status')) {
            $query->where('workflow_status', $request->get('workflow_status'));
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('service_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('service_date', '<=', $dateTo);
        }

        $services = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($services);
    }

    public function show(Service $service): JsonResponse
    {
        $service->load([
            'customer',
            'vehicle',
            'repairCategory',
            'jobcardDetail',
            'serviceObservationPoints.observationPoint',
        ]);

        return response()->json($service);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_category_id' => 'nullable|exists:repair_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_date' => 'required|date',
            'charge' => 'nullable|numeric|min:0',
        ]);

        // done_status is system-managed — never accepted from the client. A
        // jobcard is opened (0) and reaches "completed" via the controlled
        // workflow, never by injecting a status value.
        $service = Service::create([
            'customer_id' => $validated['customer_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'repair_category_id' => $validated['repair_category_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'service_date' => $validated['service_date'],
            'charge' => $validated['charge'] ?? 0,
            'done_status' => 0,
        ]);

        return response()->json($service->load('jobcardDetail'), 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        // done_status / workflow_status are system-owned; only descriptive and
        // scheduling fields may be patched, and never the completion state.
        $validated = $request->validate([
            'repair_category_id' => 'nullable|exists:repair_categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'service_date' => 'sometimes|date',
            'charge' => 'nullable|numeric|min:0',
        ]);

        $service->update($validated);

        return response()->json($service->load('jobcardDetail'));
    }

    public function complete(Service $service): JsonResponse
    {
        try {
            $result = app(ServiceService::class)->executeComplete($service);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['already'] ? 'Jobcard sudah selesai sebelumnya.' : 'Jobcard selesai.',
            'already_processed' => $result['already'],
            'invoice_id' => $result['invoice']?->id,
        ]);
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(['message' => 'Jobcard deleted.']);
    }
}
