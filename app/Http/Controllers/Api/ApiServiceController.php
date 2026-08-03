<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with(['customer', 'vehicle', 'repairCategory', 'technicians']);

        if ($request->has('done_status')) {
            $query->where('done_status', $request->get('done_status'));
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('service_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('service_date', '<=', $dateTo);
        }

        $services = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(ServiceResource::collection($services)->response()->getData(true));
    }

    public function show(Service $service): JsonResponse
    {
        $service->load([
            'customer',
            'vehicle',
            'repairCategory',
            'technicians',
            'images',
            'jobcardDetail',
            'checkoutResults',
            'invoice',
        ]);

        return response()->json(new ServiceResource($service));
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
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
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

        if (! empty($validated['technician_ids'])) {
            $service->technicians()->attach($validated['technician_ids']);
        }

        return response()->json(new ServiceResource($service->load('technicians')), 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'repair_category_id' => 'nullable|exists:repair_categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'service_date' => 'sometimes|date',
            'charge' => 'nullable|numeric|min:0',
            'done_status' => 'nullable|integer|in:0,1,2',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
        ]);

        $service->update($validated);

        if ($request->has('technician_ids')) {
            $service->technicians()->sync($validated['technician_ids']);
        }

        return response()->json(new ServiceResource($service));
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function complete(Service $service): JsonResponse
    {
        $service->update([
            'done_status' => 2,
        ]);

        return response()->json(new ServiceResource($service));
    }
}
