<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CheckoutCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = CheckoutCategory::query();
        if ($request->filled('search')) {
            $query->where('category_name', 'like', "%{$request->search}%");
        }
        $categories = $query->orderBy('category_name')->paginate(15)->withQueryString();
        return view('checkout-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('checkout-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:255', Rule::unique('checkout_categories', 'category_name')->whereNull('deleted_at')],
        ]);
        CheckoutCategory::create($validated);
        return redirect()->route('checkout-categories.index')->with('success', 'Kategori checkout ditambahkan.');
    }

    public function edit(CheckoutCategory $checkoutCategory)
    {
        return view('checkout-categories.edit', compact('checkoutCategory'));
    }

    public function update(Request $request, CheckoutCategory $checkoutCategory)
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:255', Rule::unique('checkout_categories', 'category_name')->whereNull('deleted_at')->ignore($checkoutCategory->id)],
        ]);
        $checkoutCategory->update($validated);
        return redirect()->route('checkout-categories.index')->with('success', 'Kategori checkout diperbarui.');
    }

    public function destroy(CheckoutCategory $checkoutCategory)
    {
        if ($checkoutCategory->checkoutResults()->exists()) {
            return back()->with('error', 'Kategori checkout tidak bisa dihapus karena masih dipakai di service.');
        }
        $checkoutCategory->delete();
        return redirect()->route('checkout-categories.index')->with('success', 'Kategori checkout dihapus.');
    }
}
