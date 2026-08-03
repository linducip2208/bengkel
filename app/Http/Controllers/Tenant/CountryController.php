<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $query = Country::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%");
        }
        $countries = $query->orderBy('name')->paginate(20)->withQueryString();
        return view('countries.index', compact('countries'));
    }

    public function create()
    {
        return view('countries.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'size:2', Rule::unique('countries', 'code')],
            'name' => 'required|string|max:255',
            'phone_code' => 'nullable|string|max:10',
        ]);
        Country::create([
            'code' => $validated['code'] ? strtoupper($validated['code']) : null,
            'name' => $validated['name'],
            'phone_code' => $validated['phone_code'] ?? null,
        ]);
        return redirect()->route('countries.index')->with('success', 'Negara ditambahkan.');
    }

    public function edit(Country $country)
    {
        return view('countries.edit', compact('country'));
    }

    public function update(Request $request, Country $country)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'size:2', Rule::unique('countries', 'code')->ignore($country->id)],
            'name' => 'required|string|max:255',
            'phone_code' => 'nullable|string|max:10',
        ]);
        $country->update([
            'code' => $validated['code'] ? strtoupper($validated['code']) : null,
            'name' => $validated['name'],
            'phone_code' => $validated['phone_code'] ?? null,
        ]);
        return redirect()->route('countries.index')->with('success', 'Negara diperbarui.');
    }

    public function destroy(Country $country)
    {
        if ($country->states()->exists()) {
            return back()->with('error', 'Negara tidak bisa dihapus karena masih punya provinsi terdaftar.');
        }
        $country->delete();
        return redirect()->route('countries.index')->with('success', 'Negara dihapus.');
    }
}
