<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleImageResource;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Support\IdentityNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $this->normalizeIdentity($request);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'vehicle_brand_id' => 'required|exists:vehicle_brands,id',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'number_plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'number_plate')],
            'chassis_number' => ['nullable', 'string', 'max:50', Rule::unique('vehicles', 'chassis_number')],
            'engine_number' => ['nullable', 'string', 'max:50', Rule::unique('vehicles', 'engine_number')],
            'model_name' => 'nullable|string|max:100',
            'model_year' => 'nullable|integer|min:1990|max:'.(now()->year + 1),
            'color' => 'nullable|string|max:50',
            'odometer' => 'nullable|integer|min:0',
        ]);

        $vehicle = Vehicle::create($validated);

        return response()->json(new VehicleResource($vehicle), 201);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->normalizeIdentity($request);

        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'vehicle_type_id' => 'sometimes|exists:vehicle_types,id',
            'vehicle_brand_id' => 'sometimes|exists:vehicle_brands,id',
            'fuel_type_id' => 'sometimes|exists:fuel_types,id',
            'number_plate' => ['sometimes', 'string', 'max:20', Rule::unique('vehicles', 'number_plate')->ignore($vehicle->id)],
            'chassis_number' => ['nullable', 'string', 'max:50', Rule::unique('vehicles', 'chassis_number')->ignore($vehicle->id)],
            'engine_number' => ['nullable', 'string', 'max:50', Rule::unique('vehicles', 'engine_number')->ignore($vehicle->id)],
            'model_name' => 'nullable|string|max:100',
            'model_year' => 'nullable|integer|min:1990|max:'.(now()->year + 1),
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

    public function uploadImage(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        // store() generates a random hashed filename on the public disk.
        $path = $request->file('image')->store('vehicle-images', 'public');

        $image = $vehicle->images()->create([
            'image_path' => $path,
            'caption' => $validated['caption'] ?? null,
        ]);

        return response()->json([
            'message' => 'Foto berhasil diunggah.',
            'data' => array_merge((new VehicleImageResource($image))->toArray($request), [
                'url' => url(Storage::url($path)),
            ]),
        ], 201);
    }

    public function deleteImage(Vehicle $vehicle, int $image): JsonResponse
    {
        // Scoped through the branch-checked vehicle: cross-branch ids 404.
        $vehicle->images()->findOrFail($image)->delete();

        return response()->json(['message' => 'Foto dihapus.']);
    }

    private function normalizeIdentity(Request $request): void
    {
        $normalized = [];
        foreach (['number_plate', 'chassis_number', 'engine_number'] as $field) {
            if (! $request->exists($field)) {
                continue;
            }
            $normalized[$field] = $field === 'number_plate'
                ? IdentityNormalizer::vehiclePlate($request->input($field))
                : IdentityNormalizer::serialNumber($request->input($field));
        }
        $request->merge($normalized);
    }
}
