<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\RepairCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RepairCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = RepairCategory::query();
        if ($request->filled('search')) {
            $query->where('repair_category_name', 'like', "%{$request->search}%");
        }
        $repairCategories = $query->orderBy('repair_category_name')->paginate(15)->withQueryString();

        return view('repair-categories.index', compact('repairCategories'));
    }

    public function create()
    {
        return view('repair-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('repair_categories', 'repair_category_name')->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        RepairCategory::createWithUniqueSlug([
            'repair_category_name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('repair-categories.index')->with('success', 'Kategori repair berhasil ditambahkan.');
    }

    public function edit(RepairCategory $repairCategory)
    {
        return view('repair-categories.edit', compact('repairCategory'));
    }

    public function update(Request $request, RepairCategory $repairCategory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('repair_categories', 'repair_category_name')->whereNull('deleted_at')->ignore($repairCategory->id)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $repairCategory->updateWithUniqueSlug([
            'repair_category_name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('repair-categories.index')->with('success', 'Kategori repair berhasil diperbarui.');
    }

    public function destroy(RepairCategory $repairCategory)
    {
        if ($repairCategory->services()->withoutGlobalScopes()->exists() || $repairCategory->observationPoints()->exists()) {
            return back()->with('error', 'Kategori repair tidak bisa dihapus karena masih dipakai oleh service atau observation point.');
        }
        $repairCategory->delete();

        return redirect()->route('repair-categories.index')->with('success', 'Kategori repair berhasil dihapus.');
    }
}
