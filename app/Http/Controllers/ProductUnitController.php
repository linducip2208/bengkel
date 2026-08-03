<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductUnitRequest;
use App\Models\ProductUnit;

class ProductUnitController extends Controller
{
    public function index()
    {
        $productUnits = ProductUnit::orderBy('name')->paginate(15);
        return view('product-units.index', compact('productUnits'));
    }

    public function create()
    {
        return view('product-units.create');
    }

    public function store(ProductUnitRequest $request)
    {
        ProductUnit::create($request->validated());
        return redirect()->route('product-units.index')->with('success', 'Satuan produk berhasil ditambahkan.');
    }

    public function edit(ProductUnit $productUnit)
    {
        return view('product-units.edit', compact('productUnit'));
    }

    public function update(ProductUnitRequest $request, ProductUnit $productUnit)
    {
        $productUnit->update($request->validated());
        return redirect()->route('product-units.index')->with('success', 'Satuan produk berhasil diperbarui.');
    }

    public function destroy(ProductUnit $productUnit)
    {
        $productUnit->delete();
        return redirect()->route('product-units.index')->with('success', 'Satuan produk berhasil dihapus.');
    }
}
