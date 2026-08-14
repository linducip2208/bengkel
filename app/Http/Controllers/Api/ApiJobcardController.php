<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
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
            'done_status' => 'nullable|integer|in:0,1,2',
        ]);

        $service = Service::create([
            'customer_id' => $validated['customer_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'repair_category_id' => $validated['repair_category_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'service_date' => $validated['service_date'],
            'charge' => $validated['charge'] ?? 0,
            'done_status' => $validated['done_status'] ?? 0,
        ]);

        return response()->json($service->load('jobcardDetail'), 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'repair_category_id' => 'nullable|exists:repair_categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'service_date' => 'sometimes|date',
            'charge' => 'nullable|numeric|min:0',
            'done_status' => 'nullable|integer|in:0,1,2',
        ]);

        $service->update($validated);

        return response()->json($service->load('jobcardDetail'));
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(['message' => 'Jobcard deleted.']);
    }
}
