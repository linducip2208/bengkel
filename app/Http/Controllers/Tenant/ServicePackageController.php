<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\RepairCategory;
use App\Models\ServicePackage;
use Illuminate\Http\Request;

class ServicePackageController extends Controller
{
    public function index()
    {
        $packages = ServicePackage::with('repairCategory')->orderBy('name')->paginate(15);
        return view('service-packages.index', compact('packages'));
    }

    public function create()
    {
        $categories = RepairCategory::orderBy('repair_category_name')->get();
        return view('service-packages.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'repair_category_id' => 'nullable|exists:repair_categories,id',
            'price' => 'required|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0.5',
            'description' => 'nullable|string',
            'items' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        ServicePackage::create($validated);

        return redirect()->route('service-packages.index')->with('success', 'Paket service berhasil ditambahkan.');
    }

    public function edit(ServicePackage $servicePackage)
    {
        $categories = RepairCategory::orderBy('repair_category_name')->get();
        return view('service-packages.edit', compact('servicePackage', 'categories'));
    }

    public function update(Request $request, ServicePackage $servicePackage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'repair_category_id' => 'nullable|exists:repair_categories,id',
            'price' => 'required|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0.5',
            'description' => 'nullable|string',
            'items' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $servicePackage->update($validated);

        return redirect()->route('service-packages.index')->with('success', 'Paket service berhasil diperbarui.');
    }

    public function destroy(ServicePackage $servicePackage)
    {
        $servicePackage->delete();
        return redirect()->route('service-packages.index')->with('success', 'Paket service berhasil dihapus.');
    }

    /** AJAX: return package detail for quick-fill in service form */
    public function getJson(ServicePackage $servicePackage)
    {
        return response()->json([
            'name' => $servicePackage->name,
            'price' => $servicePackage->price,
            'estimated_hours' => $servicePackage->estimated_hours,
            'description' => $servicePackage->description,
            'items' => $servicePackage->items,
        ]);
    }
}
