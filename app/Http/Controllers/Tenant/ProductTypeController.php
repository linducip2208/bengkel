<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductType::query();
        if ($request->filled('search')) {
            $query->where('type', 'like', "%{$request->search}%");
        }
        $productTypes = $query->orderBy('type')->paginate(15)->withQueryString();

        return view('product-types.index', compact('productTypes'));
    }

    public function create()
    {
        return view('product-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_types', 'type')->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        ProductType::createWithUniqueSlug([
            'type' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('product-types.index')->with('success', 'Kategori produk berhasil ditambahkan.');
    }

    public function edit(ProductType $productType)
    {
        return view('product-types.edit', compact('productType'));
    }

    public function update(Request $request, ProductType $productType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_types', 'type')->whereNull('deleted_at')->ignore($productType->id)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $productType->updateWithUniqueSlug([
            'type' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('product-types.index')->with('success', 'Kategori produk berhasil diperbarui.');
    }

    public function destroy(ProductType $productType)
    {
        if ($productType->products()->withoutGlobalScopes()->exists()) {
            return back()->with('error', 'Kategori produk tidak bisa dihapus karena masih dipakai oleh sparepart terdaftar.');
        }
        $productType->delete();

        return redirect()->route('product-types.index')->with('success', 'Kategori produk berhasil dihapus.');
    }
}
