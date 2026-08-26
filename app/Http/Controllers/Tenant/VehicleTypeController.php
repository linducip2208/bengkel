<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VehicleTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleType::query();
        if ($request->filled('search')) {
            $query->where('vehicle_type', 'like', "%{$request->search}%");
        }
        $vehicleTypes = $query->orderBy('vehicle_type')->paginate(15)->withQueryString();

        return view('vehicle-types.index', compact('vehicleTypes'));
    }

    public function create()
    {
        return view('vehicle-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('vehicle_types', 'vehicle_type')->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        VehicleType::create([
            'vehicle_type' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('vehicle-types.index')->with('success', 'Tipe kendaraan berhasil ditambahkan.');
    }

    public function edit(VehicleType $vehicleType)
    {
        return view('vehicle-types.edit', compact('vehicleType'));
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('vehicle_types', 'vehicle_type')->whereNull('deleted_at')->ignore($vehicleType->id)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $vehicleType->update([
            'vehicle_type' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('vehicle-types.index')->with('success', 'Tipe kendaraan berhasil diperbarui.');
    }

    public function destroy(VehicleType $vehicleType)
    {
        if ($vehicleType->vehicles()->withoutGlobalScopes()->exists() || $vehicleType->vehicleBrands()->exists()) {
            return back()->with('error', 'Tipe kendaraan tidak bisa dihapus karena masih dipakai oleh kendaraan atau merek terdaftar.');
        }
        $vehicleType->delete();

        return redirect()->route('vehicle-types.index')->with('success', 'Tipe kendaraan berhasil dihapus.');
    }
}
