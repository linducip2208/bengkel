<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;

class ProductVariationController extends Controller
{
    public function index(Product $product)
    {
        $variations = $product->variations()->orderBy('name')->get();

        return view('products.variations', compact('product', 'variations'));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:product_variations,sku',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
        ]);

        $data['product_id'] = $product->id;
        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        ProductVariation::create($data);

        return back()->with('success', 'Variasi ditambahkan.');
    }

    public function update(Request $request, Product $product, ProductVariation $variation)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:product_variations,sku,'.$variation->id,
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
        ]);

        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        $variation->update($data);

        return back()->with('success', 'Variasi diperbarui.');
    }

    public function destroy(Product $product, ProductVariation $variation)
    {
        $variation->delete();

        return back()->with('success', 'Variasi dihapus.');
    }
}
