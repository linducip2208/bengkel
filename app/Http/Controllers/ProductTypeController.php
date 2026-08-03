<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductTypeRequest;
use App\Models\ProductType;

class ProductTypeController extends Controller
{
    public function index()
    {
        $productTypes = ProductType::orderBy('name')->paginate(15);
        return view('product-types.index', compact('productTypes'));
    }

    public function create()
    {
        return view('product-types.create');
    }

    public function store(ProductTypeRequest $request)
    {
        ProductType::create($request->validated());
        return redirect()->route('product-types.index')->with('success', 'Tipe produk berhasil ditambahkan.');
    }

    public function edit(ProductType $productType)
    {
        return view('product-types.edit', compact('productType'));
    }

    public function update(ProductTypeRequest $request, ProductType $productType)
    {
        $productType->update($request->validated());
        return redirect()->route('product-types.index')->with('success', 'Tipe produk berhasil diperbarui.');
    }

    public function destroy(ProductType $productType)
    {
        $productType->delete();
        return redirect()->route('product-types.index')->with('success', 'Tipe produk berhasil dihapus.');
    }
}
