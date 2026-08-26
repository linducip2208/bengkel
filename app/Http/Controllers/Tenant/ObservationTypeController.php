<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ObservationType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ObservationTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ObservationType::query();
        if ($request->filled('search')) {
            $query->where('observation_type', 'like', "%{$request->search}%");
        }
        $observationTypes = $query->orderBy('observation_type')->paginate(15)->withQueryString();

        return view('observation-types.index', compact('observationTypes'));
    }

    public function create()
    {
        return view('observation-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'observation_type' => ['required', 'string', 'max:255', Rule::unique('observation_types', 'observation_type')->whereNull('deleted_at')],
        ]);

        ObservationType::create($validated);

        return redirect()->route('observation-types.index')->with('success', 'Tipe observasi berhasil ditambahkan.');
    }

    public function edit(ObservationType $observationType)
    {
        return view('observation-types.edit', compact('observationType'));
    }

    public function update(Request $request, ObservationType $observationType)
    {
        $validated = $request->validate([
            'observation_type' => ['required', 'string', 'max:255', Rule::unique('observation_types', 'observation_type')->whereNull('deleted_at')->ignore($observationType->id)],
        ]);

        $observationType->update($validated);

        return redirect()->route('observation-types.index')->with('success', 'Tipe observasi berhasil diperbarui.');
    }

    public function destroy(ObservationType $observationType)
    {
        if ($observationType->observationPoints()->exists()) {
            return back()->with('error', 'Tipe observasi tidak bisa dihapus karena masih punya checklist point terdaftar.');
        }
        $observationType->delete();

        return redirect()->route('observation-types.index')->with('success', 'Tipe observasi berhasil dihapus.');
    }
}
