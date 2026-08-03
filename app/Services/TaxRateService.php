<?php

namespace App\Services;

use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxRateService extends BaseService
{
    public function index(Request $request)
    {
        $query = TaxRate::query();
        if ($request->filled('search')) {
            $query->where('taxname', 'like', "%{$request->search}%");
        }
        return $query->orderBy('taxname')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'taxname' => 'required|string|max:255|unique:tax_rates',
            'tax' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        return TaxRate::create($validated);
    }

    public function show($id)
    {
        return TaxRate::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = TaxRate::findOrFail($id);
        $validated = $request->validate([
            'taxname' => 'required|string|max:255|unique:tax_rates,taxname,' . $id,
            'tax' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $model->update($validated);
        return $model;
    }

    public function destroy($id)
    {
        $model = TaxRate::findOrFail($id);
        $model->delete();
        return $model;
    }
}
