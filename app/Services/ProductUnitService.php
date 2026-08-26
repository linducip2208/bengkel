<?php

namespace App\Services;

use App\Models\ProductUnit;
use Illuminate\Http\Request;

class ProductUnitService extends BaseService
{
    public function index(Request $request)
    {
        $query = ProductUnit::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        return $query->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_units',
            'abbreviation' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        return ProductUnit::create($validated);
    }

    public function show($id)
    {
        return ProductUnit::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = ProductUnit::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_units,name,'.$id,
            'abbreviation' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        $model->update($validated);

        return $model;
    }

    public function destroy($id)
    {
        $model = ProductUnit::findOrFail($id);
        $model->delete();

        return $model;
    }
}
