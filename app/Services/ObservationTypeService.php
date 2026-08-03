<?php

namespace App\Services;

use App\Models\ObservationType;
use Illuminate\Http\Request;

class ObservationTypeService extends BaseService
{
    public function index(Request $request)
    {
        $query = ObservationType::query();
        if ($request->filled('search')) {
            $query->where('observation_type', 'like', "%{$request->search}%");
        }
        return $query->orderBy('observation_type')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'observation_type' => 'required|string|max:255|unique:observation_types',
        ]);
        return ObservationType::create($validated);
    }

    public function show($id)
    {
        return ObservationType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = ObservationType::findOrFail($id);
        $validated = $request->validate([
            'observation_type' => 'required|string|max:255|unique:observation_types,observation_type,' . $id,
        ]);
        $model->update($validated);
        return $model;
    }

    public function destroy($id)
    {
        $model = ObservationType::findOrFail($id);
        $model->delete();
        return $model;
    }
}
