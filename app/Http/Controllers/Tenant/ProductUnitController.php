<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductUnit::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        $productUnits = $query->orderBy('name')->paginate(15)->withQueryString();
        return view('product-units.index', compact('productUnits'));
    }

    public function create()
    {
        return view('product-units.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_units', 'name')->whereNull('deleted_at')],
            'abbreviation' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        ProductUnit::create([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'] ?? $validated['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('product-units.index')->with('success', 'Satuan produk berhasil ditambahkan.');
    }

    public function edit(ProductUnit $productUnit)
    {
        return view('product-units.edit', compact('productUnit'));
    }

    public function update(Request $request, ProductUnit $productUnit)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_units', 'name')->whereNull('deleted_at')->ignore($productUnit->id)],
            'abbreviation' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $productUnit->update([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'] ?? $validated['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('product-units.index')->with('success', 'Satuan produk berhasil diperbarui.');
    }

    public function destroy(ProductUnit $productUnit)
    {
        if (Product::withoutGlobalScopes()->where('unit_id', $productUnit->id)->exists()) {
            return back()->with('error', 'Satuan produk tidak bisa dihapus karena masih dipakai oleh sparepart terdaftar.');
        }
        $productUnit->delete();
        return redirect()->route('product-units.index')->with('success', 'Satuan produk berhasil dihapus.');
    }
}
