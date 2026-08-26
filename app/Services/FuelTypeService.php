<?php

namespace App\Services;

use App\Models\FuelType;
use Illuminate\Http\Request;

class FuelTypeService extends BaseService
{
    public function index(Request $request)
    {
        $query = FuelType::query();
        if ($request->filled('search')) {
            $query->where('fuel_type', 'like', "%{$request->search}%");
        }

        return $query->orderBy('fuel_type')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fuel_type' => 'required|string|max:255|unique:fuel_types',
            'slug' => 'nullable|string|max:255|unique:fuel_types',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return FuelType::create($validated);
    }

    public function show($id)
    {
        return FuelType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = FuelType::findOrFail($id);
        $validated = $request->validate([
            'fuel_type' => 'required|string|max:255|unique:fuel_types,fuel_type,'.$id,
            'slug' => 'nullable|string|max:255|unique:fuel_types,slug,'.$id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $model->update($validated);

        return $model;
    }

    public function destroy($id)
    {
        $model = FuelType::findOrFail($id);
        $model->delete();

        return $model;
    }
}
