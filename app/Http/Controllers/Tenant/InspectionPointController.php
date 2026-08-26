<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InspectionPointsLibrary;
use App\Models\ObservationType;
use Illuminate\Http\Request;

class InspectionPointController extends Controller
{
    public function index(Request $request)
    {
        $query = InspectionPointsLibrary::with('observationType');
        if ($request->filled('observation_type_id')) {
            $query->where('observation_type_id', $request->observation_type_id);
        }
        if ($request->filled('search')) {
            $query->where('point', 'like', "%{$request->search}%");
        }
        $points = $query->orderBy('sort_order')->orderBy('point')->paginate(20)->withQueryString();
        $observationTypes = ObservationType::orderBy('observation_type')->get();

        return view('inspection-points.index', compact('points', 'observationTypes'));
    }

    public function create()
    {
        $observationTypes = ObservationType::orderBy('observation_type')->get();

        return view('inspection-points.create', compact('observationTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'observation_type_id' => 'nullable|exists:observation_types,id',
            'point' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        InspectionPointsLibrary::create([
            'observation_type_id' => $validated['observation_type_id'] ?? null,
            'point' => $validated['point'],
            'category' => $validated['category'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inspection-points.index')->with('success', 'Inspection point ditambahkan.');
    }

    public function edit(InspectionPointsLibrary $inspectionPoint)
    {
        $observationTypes = ObservationType::orderBy('observation_type')->get();

        return view('inspection-points.edit', ['point' => $inspectionPoint, 'observationTypes' => $observationTypes]);
    }

    public function update(Request $request, InspectionPointsLibrary $inspectionPoint)
    {
        $validated = $request->validate([
            'observation_type_id' => 'nullable|exists:observation_types,id',
            'point' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $inspectionPoint->update([
            'observation_type_id' => $validated['observation_type_id'] ?? null,
            'point' => $validated['point'],
            'category' => $validated['category'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inspection-points.index')->with('success', 'Inspection point diperbarui.');
    }

    public function destroy(InspectionPointsLibrary $inspectionPoint)
    {
        $inspectionPoint->delete();

        return redirect()->route('inspection-points.index')->with('success', 'Inspection point dihapus.');
    }
}
