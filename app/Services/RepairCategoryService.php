<?php

namespace App\Services;

use App\Models\RepairCategory;
use Illuminate\Http\Request;

class RepairCategoryService extends BaseService
{
    public function index(Request $request)
    {
        $query = RepairCategory::query();
        if ($request->filled('search')) {
            $query->where('repair_category_name', 'like', "%{$request->search}%");
        }
        return $query->orderBy('repair_category_name')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'repair_category_name' => 'required|string|max:255|unique:repair_categories',
            'slug' => 'nullable|string|max:255|unique:repair_categories',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        return RepairCategory::create($validated);
    }

    public function show($id)
    {
        return RepairCategory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = RepairCategory::findOrFail($id);
        $validated = $request->validate([
            'repair_category_name' => 'required|string|max:255|unique:repair_categories,repair_category_name,' . $id,
            'slug' => 'nullable|string|max:255|unique:repair_categories,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $model->update($validated);
        return $model;
    }

    public function destroy($id)
    {
        $model = RepairCategory::findOrFail($id);
        $model->delete();
        return $model;
    }
}
