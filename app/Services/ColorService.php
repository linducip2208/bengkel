<?php

namespace App\Services;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorService extends BaseService
{
    public function index(Request $request)
    {
        $query = Color::query();
        if ($request->filled('search')) {
            $query->where('color', 'like', "%{$request->search}%");
        }
        return $query->orderBy('color')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'color' => 'required|string|max:255|unique:colors',
            'hex_code' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);
        return Color::create($validated);
    }

    public function show($id)
    {
        return Color::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = Color::findOrFail($id);
        $validated = $request->validate([
            'color' => 'required|string|max:255|unique:colors,color,' . $id,
            'hex_code' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);
        $model->update($validated);
        return $model;
    }

    public function destroy($id)
    {
        $model = Color::findOrFail($id);
        $model->delete();
        return $model;
    }
}
