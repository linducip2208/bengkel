<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SellingPriceGroup;
use Illuminate\Http\Request;

class SellingPriceGroupController extends Controller
{
    public function index()
    {
        $groups = SellingPriceGroup::withCount(['productSellingPrices', 'customerGroups'])
            ->orderBy('name')
            ->get();

        return view('selling-price-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('selling-price-groups.create');
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $v['is_active'] = $request->boolean('is_active');

        SellingPriceGroup::create($v);

        return redirect()->route('selling-price-groups.index')->with('success', 'Grup harga jual ditambahkan.');
    }

    public function edit(SellingPriceGroup $sellingPriceGroup)
    {
        return view('selling-price-groups.edit', compact('sellingPriceGroup'));
    }

    public function update(Request $request, SellingPriceGroup $sellingPriceGroup)
    {
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $v['is_active'] = $request->boolean('is_active');

        $sellingPriceGroup->update($v);

        return redirect()->route('selling-price-groups.index')->with('success', 'Grup harga jual diperbarui.');
    }

    public function destroy(SellingPriceGroup $sellingPriceGroup)
    {
        $sellingPriceGroup->delete();

        return redirect()->route('selling-price-groups.index')->with('success', 'Grup harga jual dihapus.');
    }

    public function prices(SellingPriceGroup $sellingPriceGroup)
    {
        $products = Product::orderBy('name')->get();
        $existing = $sellingPriceGroup->productSellingPrices()->pluck('price', 'product_id');

        return view('selling-price-groups.prices', compact('sellingPriceGroup', 'products', 'existing'));
    }

    public function setProductPrices(Request $request, SellingPriceGroup $sellingPriceGroup)
    {
        $request->validate([
            'prices' => 'nullable|array',
            'prices.*' => 'nullable|numeric|min:0',
        ]);

        foreach (($request->input('prices', [])) as $productId => $price) {
            if ($price === null || $price === '') {
                $sellingPriceGroup->productSellingPrices()->where('product_id', $productId)->delete();
                continue;
            }

            $sellingPriceGroup->productSellingPrices()->updateOrCreate(
                ['product_id' => $productId],
                ['price' => $price]
            );
        }

        return redirect()->route('selling-price-groups.prices', $sellingPriceGroup)
            ->with('success', 'Harga jual per produk diperbarui.');
    }
}
