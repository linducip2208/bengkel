<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use Illuminate\Http\Request;

class ObservationPointController extends Controller
{
    public function index(Request $request)
    {
        $query = ObservationPoint::with('observationType');
        if ($request->filled('observation_type_id')) {
            $query->where('observation_type_id', $request->observation_type_id);
        }
        if ($request->filled('search')) {
            $query->where('observation_point', 'like', "%{$request->search}%");
        }
        $observationPoints = $query->orderBy('observation_type_id')->orderBy('observation_point')
            ->paginate(20)->withQueryString();
        $observationTypes = ObservationType::orderBy('observation_type')->get();
        return view('observation-points.index', compact('observationPoints', 'observationTypes'));
    }

    public function create()
    {
        $observationTypes = ObservationType::orderBy('observation_type')->get();
        return view('observation-points.create', compact('observationTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'observation_type_id' => 'required|exists:observation_types,id',
            'observation_point' => 'required|string|max:255',
        ]);

        ObservationPoint::create($validated);

        return redirect()->route('observation-points.index')->with('success', 'Observation point ditambahkan.');
    }

    public function edit(ObservationPoint $observationPoint)
    {
        $observationTypes = ObservationType::orderBy('observation_type')->get();
        return view('observation-points.edit', compact('observationPoint', 'observationTypes'));
    }

    public function update(Request $request, ObservationPoint $observationPoint)
    {
        $validated = $request->validate([
            'observation_type_id' => 'required|exists:observation_types,id',
            'observation_point' => 'required|string|max:255',
        ]);

        $observationPoint->update($validated);

        return redirect()->route('observation-points.index')->with('success', 'Observation point diperbarui.');
    }

    public function destroy(ObservationPoint $observationPoint)
    {
        if ($observationPoint->serviceObservationPoints()->exists()) {
            return back()->with('error', 'Point tidak bisa dihapus karena sudah dipakai di service.');
        }
        $observationPoint->delete();
        return redirect()->route('observation-points.index')->with('success', 'Observation point dihapus.');
    }
}
