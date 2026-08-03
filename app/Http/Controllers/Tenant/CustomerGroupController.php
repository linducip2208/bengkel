<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index()
    {
        $groups = CustomerGroup::withCount('customers')->orderBy('name')->get();
        return view('customer-groups.index', compact('groups'));
    }

    public function create() { return view('customer-groups.create'); }

    public function store(Request $request)
    {
        $v = $request->validate(['name'=>'required|string|max:255','description'=>'nullable|string']);
        CustomerGroup::create($v);
        return redirect()->route('customer-groups.index')->with('success', 'Group ditambahkan.');
    }

    public function show(CustomerGroup $customerGroup)
    {
        $customers = $customerGroup->customers()->orderBy('name')->paginate(20);
        return view('customer-groups.show', compact('customerGroup', 'customers'));
    }

    public function edit(CustomerGroup $customerGroup) { return view('customer-groups.edit', compact('customerGroup')); }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $v = $request->validate(['name'=>'required|string|max:255','description'=>'nullable|string']);
        $customerGroup->update($v);
        return redirect()->route('customer-groups.index')->with('success', 'Group diperbarui.');
    }

    public function destroy(CustomerGroup $customerGroup) { $customerGroup->delete(); return back()->with('success', 'Group dihapus.'); }
}
