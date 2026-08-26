<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxRateController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxRate::query();
        if ($request->filled('search')) {
            $query->where('taxname', 'like', "%{$request->search}%");
        }
        $taxRates = $query->orderBy('taxname')->paginate(15)->withQueryString();

        return view('tax-rates.index', compact('taxRates'));
    }

    public function create()
    {
        return view('tax-rates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tax_rates', 'taxname')->whereNull('deleted_at')],
            'rate' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        TaxRate::create([
            'taxname' => $validated['name'],
            'tax' => $validated['rate'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('tax-rates.index')->with('success', 'Tarif pajak berhasil ditambahkan.');
    }

    public function edit(TaxRate $taxRate)
    {
        return view('tax-rates.edit', compact('taxRate'));
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tax_rates', 'taxname')->whereNull('deleted_at')->ignore($taxRate->id)],
            'rate' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $taxRate->update([
            'taxname' => $validated['name'],
            'tax' => $validated['rate'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('tax-rates.index')->with('success', 'Tarif pajak berhasil diperbarui.');
    }

    public function destroy(TaxRate $taxRate)
    {
        if ($taxRate->serviceTaxes()->exists()) {
            return back()->with('error', 'Tarif pajak tidak bisa dihapus karena masih dipakai oleh service terdaftar.');
        }
        $taxRate->delete();

        return redirect()->route('tax-rates.index')->with('success', 'Tarif pajak berhasil dihapus.');
    }
}
