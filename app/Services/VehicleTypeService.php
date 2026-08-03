<?php

namespace App\Services;

use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleTypeService extends BaseService
{
    public function index(Request $request)
    {
        $query = VehicleType::query();
        if ($request->filled('search')) {
            $query->where('vehicle_type', 'like', "%{$request->search}%");
        }
        return $query->orderBy('vehicle_type')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_type' => 'required|string|max:255|unique:vehicle_types',
            'slug' => 'nullable|string|max:255|unique:vehicle_types',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        return VehicleType::create($validated);
    }

    public function show($id)
    {
        return VehicleType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = VehicleType::findOrFail($id);
        $validated = $request->validate([
            'vehicle_type' => 'required|string|max:255|unique:vehicle_types,vehicle_type,' . $id,
            'slug' => 'nullable|string|max:255|unique:vehicle_types,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $model->update($validated);
        return $model;
    }

    public function destroy($id)
    {
        $model = VehicleType::findOrFail($id);
        $model->delete();
        return $model;
    }
}
