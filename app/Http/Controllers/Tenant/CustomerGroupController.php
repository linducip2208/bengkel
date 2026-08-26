<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Models\SellingPriceGroup;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index()
    {
        $groups = CustomerGroup::withCount('customers')->orderBy('name')->get();

        return view('customer-groups.index', compact('groups'));
    }

    public function create()
    {
        $sellingPriceGroups = SellingPriceGroup::where('is_active', true)->orderBy('name')->get();

        return view('customer-groups.create', compact('sellingPriceGroups'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'selling_price_group_id' => 'nullable|exists:selling_price_groups,id',
        ]);
        CustomerGroup::create($v);

        return redirect()->route('customer-groups.index')->with('success', 'Group ditambahkan.');
    }

    public function show(CustomerGroup $customerGroup)
    {
        $customers = $customerGroup->customers()->orderBy('name')->paginate(20);

        return view('customer-groups.show', compact('customerGroup', 'customers'));
    }

    public function edit(CustomerGroup $customerGroup)
    {
        $sellingPriceGroups = SellingPriceGroup::where('is_active', true)->orderBy('name')->get();

        return view('customer-groups.edit', compact('customerGroup', 'sellingPriceGroups'));
    }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'selling_price_group_id' => 'nullable|exists:selling_price_groups,id',
        ]);
        $customerGroup->update($v);

        return redirect()->route('customer-groups.index')->with('success', 'Group diperbarui.');
    }

    public function destroy(CustomerGroup $customerGroup)
    {
        $customerGroup->delete();

        return back()->with('success', 'Group dihapus.');
    }
}
