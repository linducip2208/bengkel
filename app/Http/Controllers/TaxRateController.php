<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaxRateRequest;
use App\Models\TaxRate;

class TaxRateController extends Controller
{
    public function index()
    {
        $taxRates = TaxRate::orderBy('name')->paginate(15);
        return view('tax-rates.index', compact('taxRates'));
    }

    public function create()
    {
        return view('tax-rates.create');
    }

    public function store(TaxRateRequest $request)
    {
        TaxRate::create($request->validated());
        return redirect()->route('tax-rates.index')->with('success', 'Tarif pajak berhasil ditambahkan.');
    }

    public function edit(TaxRate $taxRate)
    {
        return view('tax-rates.edit', compact('taxRate'));
    }

    public function update(TaxRateRequest $request, TaxRate $taxRate)
    {
        $taxRate->update($request->validated());
        return redirect()->route('tax-rates.index')->with('success', 'Tarif pajak berhasil diperbarui.');
    }

    public function destroy(TaxRate $taxRate)
    {
        $taxRate->delete();
        return redirect()->route('tax-rates.index')->with('success', 'Tarif pajak berhasil dihapus.');
    }
}
