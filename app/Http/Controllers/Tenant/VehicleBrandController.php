<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleBrandController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleBrand::with('vehicleType');
        if ($request->filled('search')) {
            $query->where('vehicle_brand', 'like', "%{$request->search}%");
        }
        $vehicleBrands = $query->orderBy('vehicle_brand')->paginate(15)->withQueryString();
        return view('vehicle-brands.index', compact('vehicleBrands'));
    }

    public function create()
    {
        $vehicleTypes = VehicleType::orderBy('vehicle_type')->get();
        return view('vehicle-brands.create', compact('vehicleTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_type_id' => ['required', Rule::exists('vehicle_types', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255', Rule::unique('vehicle_brands', 'vehicle_brand')->whereNull('deleted_at')],
        ]);

        VehicleBrand::create([
            'vehicle_type_id' => $validated['vehicle_type_id'],
            'vehicle_brand' => $validated['name'],
        ]);

        return redirect()->route('vehicle-brands.index')->with('success', 'Merek kendaraan berhasil ditambahkan.');
    }

    public function edit(VehicleBrand $vehicleBrand)
    {
        $vehicleTypes = VehicleType::orderBy('vehicle_type')->get();
        return view('vehicle-brands.edit', compact('vehicleBrand', 'vehicleTypes'));
    }

    public function update(Request $request, VehicleBrand $vehicleBrand)
    {
        $validated = $request->validate([
            'vehicle_type_id' => ['required', Rule::exists('vehicle_types', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255', Rule::unique('vehicle_brands', 'vehicle_brand')->whereNull('deleted_at')->ignore($vehicleBrand->id)],
        ]);

        $vehicleBrand->update([
            'vehicle_type_id' => $validated['vehicle_type_id'],
            'vehicle_brand' => $validated['name'],
        ]);

        return redirect()->route('vehicle-brands.index')->with('success', 'Merek kendaraan berhasil diperbarui.');
    }

    public function destroy(VehicleBrand $vehicleBrand)
    {
        if ($vehicleBrand->vehicles()->withoutGlobalScopes()->exists()) {
            return back()->with('error', 'Merek kendaraan tidak bisa dihapus karena masih dipakai oleh kendaraan terdaftar.');
        }
        $vehicleBrand->delete();
        return redirect()->route('vehicle-brands.index')->with('success', 'Merek kendaraan berhasil dihapus.');
    }
}
