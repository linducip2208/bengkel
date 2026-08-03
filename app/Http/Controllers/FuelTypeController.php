<?php

namespace App\Http\Controllers;

use App\Http\Requests\FuelTypeRequest;
use App\Models\FuelType;

class FuelTypeController extends Controller
{
    public function index()
    {
        $fuelTypes = FuelType::orderBy('name')->paginate(15);
        return view('fuel-types.index', compact('fuelTypes'));
    }

    public function create()
    {
        return view('fuel-types.create');
    }

    public function store(FuelTypeRequest $request)
    {
        FuelType::create($request->validated());
        return redirect()->route('fuel-types.index')->with('success', 'Jenis bahan bakar berhasil ditambahkan.');
    }

    public function edit(FuelType $fuelType)
    {
        return view('fuel-types.edit', compact('fuelType'));
    }

    public function update(FuelTypeRequest $request, FuelType $fuelType)
    {
        $fuelType->update($request->validated());
        return redirect()->route('fuel-types.index')->with('success', 'Jenis bahan bakar berhasil diperbarui.');
    }

    public function destroy(FuelType $fuelType)
    {
        $fuelType->delete();
        return redirect()->route('fuel-types.index')->with('success', 'Jenis bahan bakar berhasil dihapus.');
    }
}
