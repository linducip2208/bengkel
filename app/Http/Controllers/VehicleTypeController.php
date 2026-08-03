<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleTypeRequest;
use App\Models\VehicleType;

class VehicleTypeController extends Controller
{
    public function index()
    {
        $vehicleTypes = VehicleType::orderBy('name')->paginate(15);
        return view('vehicle-types.index', compact('vehicleTypes'));
    }

    public function create()
    {
        return view('vehicle-types.create');
    }

    public function store(VehicleTypeRequest $request)
    {
        VehicleType::create($request->validated());
        return redirect()->route('vehicle-types.index')->with('success', 'Tipe kendaraan berhasil ditambahkan.');
    }

    public function edit(VehicleType $vehicleType)
    {
        return view('vehicle-types.edit', compact('vehicleType'));
    }

    public function update(VehicleTypeRequest $request, VehicleType $vehicleType)
    {
        $vehicleType->update($request->validated());
        return redirect()->route('vehicle-types.index')->with('success', 'Tipe kendaraan berhasil diperbarui.');
    }

    public function destroy(VehicleType $vehicleType)
    {
        $vehicleType->delete();
        return redirect()->route('vehicle-types.index')->with('success', 'Tipe kendaraan berhasil dihapus.');
    }
}
