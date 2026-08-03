<?php

namespace App\Http\Controllers;

use App\Http\Requests\ObservationTypeRequest;
use App\Models\ObservationType;

class ObservationTypeController extends Controller
{
    public function index()
    {
        $observationTypes = ObservationType::orderBy('observation_type')->paginate(15);
        return view('observation-types.index', compact('observationTypes'));
    }

    public function create()
    {
        return view('observation-types.create');
    }

    public function store(ObservationTypeRequest $request)
    {
        ObservationType::create($request->validated());
        return redirect()->route('observation-types.index')->with('success', 'Tipe observasi berhasil ditambahkan.');
    }

    public function edit(ObservationType $observationType)
    {
        return view('observation-types.edit', compact('observationType'));
    }

    public function update(ObservationTypeRequest $request, ObservationType $observationType)
    {
        $observationType->update($request->validated());
        return redirect()->route('observation-types.index')->with('success', 'Tipe observasi berhasil diperbarui.');
    }

    public function destroy(ObservationType $observationType)
    {
        $observationType->delete();
        return redirect()->route('observation-types.index')->with('success', 'Tipe observasi berhasil dihapus.');
    }
}
