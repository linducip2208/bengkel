<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleBrandRequest;
use App\Models\VehicleBrand;
use App\Models\VehicleType;

class VehicleBrandController extends Controller
{
    public function index()
    {
        $vehicleBrands = VehicleBrand::with('vehicleType')->orderBy('name')->paginate(15);
        return view('vehicle-brands.index', compact('vehicleBrands'));
    }

    public function create()
    {
        $vehicleTypes = VehicleType::orderBy('name')->get();
        return view('vehicle-brands.create', compact('vehicleTypes'));
    }

    public function store(VehicleBrandRequest $request)
    {
        VehicleBrand::create($request->validated());
        return redirect()->route('vehicle-brands.index')->with('success', 'Merek kendaraan berhasil ditambahkan.');
    }

    public function edit(VehicleBrand $vehicleBrand)
    {
        $vehicleTypes = VehicleType::orderBy('name')->get();
        return view('vehicle-brands.edit', compact('vehicleBrand', 'vehicleTypes'));
    }

    public function update(VehicleBrandRequest $request, VehicleBrand $vehicleBrand)
    {
        $vehicleBrand->update($request->validated());
        return redirect()->route('vehicle-brands.index')->with('success', 'Merek kendaraan berhasil diperbarui.');
    }

    public function destroy(VehicleBrand $vehicleBrand)
    {
        $vehicleBrand->delete();
        return redirect()->route('vehicle-brands.index')->with('success', 'Merek kendaraan berhasil dihapus.');
    }
}
