<?php

namespace App\Services;

use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeService extends BaseService
{
    public function index(Request $request)
    {
        $query = ProductType::query();
        if ($request->filled('search')) {
            $query->where('type', 'like', "%{$request->search}%");
        }

        return $query->orderBy('type')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:255|unique:product_types',
            'slug' => 'nullable|string|max:255|unique:product_types',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return ProductType::createWithUniqueSlug($validated);
    }

    public function show($id)
    {
        return ProductType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = ProductType::findOrFail($id);
        $validated = $request->validate([
            'type' => 'required|string|max:255|unique:product_types,type,'.$id,
            'slug' => 'nullable|string|max:255|unique:product_types,slug,'.$id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $model->updateWithUniqueSlug($validated);

        return $model;
    }

    public function destroy($id)
    {
        $model = ProductType::findOrFail($id);
        $model->delete();

        return $model;
    }
}
