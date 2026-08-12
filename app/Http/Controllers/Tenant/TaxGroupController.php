<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TaxGroup;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaxGroupController extends Controller
{
    public function index(Request $request)
    {
        $taxGroups = TaxGroup::query()
            ->with('rates')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('tax-groups.index', compact('taxGroups'));
    }

    public function create()
    {
        $taxRates = TaxRate::where('is_active', true)->orderBy('taxname')->get();

        return view('tax-groups.create', compact('taxRates'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $taxGroup = DB::transaction(function () use ($validated) {
            $taxGroup = TaxGroup::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $taxGroup->rates()->sync($validated['tax_rate_ids'] ?? []);

            return $taxGroup;
        });

        return redirect()->route('tax-groups.index')
            ->with('success', 'Grup pajak berhasil ditambahkan.');
    }

    public function edit(TaxGroup $taxGroup)
    {
        $taxGroup->load('rates');
        $taxRates = TaxRate::where('is_active', true)->orderBy('taxname')->get();

        return view('tax-groups.edit', compact('taxGroup', 'taxRates'));
    }

    public function update(Request $request, TaxGroup $taxGroup)
    {
        $validated = $this->validateData($request, $taxGroup->id);

        DB::transaction(function () use ($taxGroup, $validated, $request) {
            $taxGroup->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $taxGroup->rates()->sync($validated['tax_rate_ids'] ?? []);
        });

        return redirect()->route('tax-groups.index')
            ->with('success', 'Grup pajak berhasil diperbarui.');
    }

    public function destroy(TaxGroup $taxGroup)
    {
        $taxGroup->delete();

        return redirect()->route('tax-groups.index')
            ->with('success', 'Grup pajak berhasil dihapus.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('tax_groups', 'name')->ignore($ignoreId),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'tax_rate_ids' => 'nullable|array',
            'tax_rate_ids.*' => 'exists:tax_rates,id',
        ]);
    }
}
