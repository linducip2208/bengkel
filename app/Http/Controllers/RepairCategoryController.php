<?php

namespace App\Http\Controllers;

use App\Http\Requests\RepairCategoryRequest;
use App\Models\RepairCategory;

class RepairCategoryController extends Controller
{
    public function index()
    {
        $repairCategories = RepairCategory::orderBy('name')->paginate(15);
        return view('repair-categories.index', compact('repairCategories'));
    }

    public function create()
    {
        return view('repair-categories.create');
    }

    public function store(RepairCategoryRequest $request)
    {
        RepairCategory::create($request->validated());
        return redirect()->route('repair-categories.index')->with('success', 'Kategori perbaikan berhasil ditambahkan.');
    }

    public function edit(RepairCategory $repairCategory)
    {
        return view('repair-categories.edit', compact('repairCategory'));
    }

    public function update(RepairCategoryRequest $request, RepairCategory $repairCategory)
    {
        $repairCategory->update($request->validated());
        return redirect()->route('repair-categories.index')->with('success', 'Kategori perbaikan berhasil diperbarui.');
    }

    public function destroy(RepairCategory $repairCategory)
    {
        $repairCategory->delete();
        return redirect()->route('repair-categories.index')->with('success', 'Kategori perbaikan berhasil dihapus.');
    }
}
