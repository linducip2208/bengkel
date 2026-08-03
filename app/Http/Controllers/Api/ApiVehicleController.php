<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiVehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query()->with(['customer', 'vehicleType', 'vehicleBrand', 'fuelType']);

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number_plate', 'like', "%{$search}%")
                    ->orWhere('engine_number', 'like', "%{$search}%")
                    ->orWhere('chassis_number', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json(VehicleResource::collection($vehicles)->response()->getData(true));
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        $vehicle->load(['customer', 'vehicleType', 'vehicleBrand', 'fuelType', 'services']);

        return response()->json(new VehicleResource($vehicle));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'vehicle_brand_id' => 'required|exists:vehicle_brands,id',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'number_plate' => 'required|string|max:20|unique:vehicles,number_plate',
            'chassis_number' => 'nullable|string|max:50',
            'engine_number' => 'nullable|string|max:50',
            'model_name' => 'nullable|string|max:100',
            'model_year' => 'nullable|integer|min:1990|max:' . (now()->year + 1),
            'color' => 'nullable|string|max:50',
            'odometer' => 'nullable|integer|min:0',
        ]);

        $vehicle = Vehicle::create($validated);

        return response()->json(new VehicleResource($vehicle), 201);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'vehicle_type_id' => 'sometimes|exists:vehicle_types,id',
            'vehicle_brand_id' => 'sometimes|exists:vehicle_brands,id',
            'fuel_type_id' => 'sometimes|exists:fuel_types,id',
            'number_plate' => 'sometimes|string|max:20|unique:vehicles,number_plate,' . $vehicle->id,
            'chassis_number' => 'nullable|string|max:50',
            'engine_number' => 'nullable|string|max:50',
            'model_name' => 'nullable|string|max:100',
            'model_year' => 'nullable|integer|min:1990|max:' . (now()->year + 1),
            'color' => 'nullable|string|max:50',
            'odometer' => 'nullable|integer|min:0',
        ]);

        $vehicle->update($validated);

        return response()->json(new VehicleResource($vehicle));
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted.']);
    }
}
