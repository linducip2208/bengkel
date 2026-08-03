<?php

namespace App\Services;

use App\Models\VehicleBrand;
use Illuminate\Http\Request;

class VehicleBrandService extends BaseService
{
    public function index(Request $request)
    {
        $query = VehicleBrand::query();
        if ($request->filled('search')) {
            $query->where('vehicle_brand', 'like', "%{$request->search}%");
        }
        return $query->orderBy('vehicle_brand')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'vehicle_brand' => 'required|string|max:255|unique:vehicle_brands',
        ]);
        return VehicleBrand::create($validated);
    }

    public function show($id)
    {
        return VehicleBrand::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = VehicleBrand::findOrFail($id);
        $validated = $request->validate([
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'vehicle_brand' => 'required|string|max:255|unique:vehicle_brands,vehicle_brand,' . $id,
        ]);
        $model->update($validated);
        return $model;
    }

    public function destroy($id)
    {
        $model = VehicleBrand::findOrFail($id);
        $model->delete();
        return $model;
    }
}
