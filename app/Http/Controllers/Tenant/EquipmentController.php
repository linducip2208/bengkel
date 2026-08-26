<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%");
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        $equipment = $query->paginate(15)->withQueryString();
        $categories = ['Lift/Hoist', 'Diagnostic Tool', 'Hand Tool', 'Air Tool', 'Compressor', 'Welding', 'Tire', 'Other'];

        return view('equipment.index', compact('equipment', 'categories'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $categories = ['Lift/Hoist', 'Diagnostic Tool', 'Hand Tool', 'Air Tool', 'Compressor', 'Welding', 'Tire', 'Other'];

        return view('equipment.create', compact('branches', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:equipment,code',
            'category' => 'required|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,in_use,maintenance,broken',
            'next_maintenance_date' => 'nullable|date',
            'maintenance_interval_days' => 'nullable|integer|min:1',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        Equipment::create($validated);

        return redirect()->route('equipment.index')
            ->with('success', 'Peralatan berhasil ditambahkan.');
    }

    public function show(Equipment $equipment)
    {
        $equipment->load('maintenanceLogs');

        return view('equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $categories = ['Lift/Hoist', 'Diagnostic Tool', 'Hand Tool', 'Air Tool', 'Compressor', 'Welding', 'Tire', 'Other'];

        return view('equipment.edit', compact('equipment', 'branches', 'categories'));
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('equipment', 'code')->ignore($equipment->id)],
            'category' => 'required|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,in_use,maintenance,broken',
            'next_maintenance_date' => 'nullable|date',
            'maintenance_interval_days' => 'nullable|integer|min:1',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $equipment->update($validated);

        return redirect()->route('equipment.index')
            ->with('success', 'Peralatan berhasil diperbarui.');
    }

    public function destroy(Equipment $equipment)
    {
        $equipment->delete();

        return redirect()->route('equipment.index')
            ->with('success', 'Peralatan berhasil dihapus.');
    }
}
