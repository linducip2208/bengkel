<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\FuelType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FuelTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = FuelType::query();
        if ($request->filled('search')) {
            $query->where('fuel_type', 'like', "%{$request->search}%");
        }
        $fuelTypes = $query->orderBy('fuel_type')->paginate(15)->withQueryString();

        return view('fuel-types.index', compact('fuelTypes'));
    }

    public function create()
    {
        return view('fuel-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('fuel_types', 'fuel_type')->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        FuelType::createWithUniqueSlug([
            'fuel_type' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('fuel-types.index')->with('success', 'Jenis BBM berhasil ditambahkan.');
    }

    public function edit(FuelType $fuelType)
    {
        return view('fuel-types.edit', compact('fuelType'));
    }

    public function update(Request $request, FuelType $fuelType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('fuel_types', 'fuel_type')->whereNull('deleted_at')->ignore($fuelType->id)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $fuelType->updateWithUniqueSlug([
            'fuel_type' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('fuel-types.index')->with('success', 'Jenis BBM berhasil diperbarui.');
    }

    public function destroy(FuelType $fuelType)
    {
        if ($fuelType->vehicles()->withoutGlobalScopes()->exists()) {
            return back()->with('error', 'Jenis BBM tidak bisa dihapus karena masih dipakai oleh kendaraan terdaftar.');
        }
        $fuelType->delete();

        return redirect()->route('fuel-types.index')->with('success', 'Jenis BBM berhasil dihapus.');
    }
}
