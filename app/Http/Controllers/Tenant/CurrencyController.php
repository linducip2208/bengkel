<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Currency::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%");
        }
        $currencies = $query->orderByDesc('is_default')->orderBy('code')->paginate(15)->withQueryString();

        return view('currencies.index', compact('currencies'));
    }

    public function create()
    {
        return view('currencies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:3', Rule::unique('currencies', 'code')],
            'name' => 'required|string|max:255',
            'symbol' => 'nullable|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            if ($request->boolean('is_default')) {
                Currency::where('is_default', true)->update(['is_default' => false]);
            }
            Currency::create([
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'symbol' => $validated['symbol'] ?? null,
                'exchange_rate' => $validated['exchange_rate'],
                'is_default' => $request->boolean('is_default'),
            ]);
        });

        return redirect()->route('currencies.index')->with('success', 'Currency ditambahkan.');
    }

    public function edit(Currency $currency)
    {
        return view('currencies.edit', compact('currency'));
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:3', Rule::unique('currencies', 'code')->ignore($currency->id)],
            'name' => 'required|string|max:255',
            'symbol' => 'nullable|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $currency, $request) {
            if ($request->boolean('is_default')) {
                Currency::where('id', '!=', $currency->id)->where('is_default', true)->update(['is_default' => false]);
            }
            $currency->update([
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'symbol' => $validated['symbol'] ?? null,
                'exchange_rate' => $validated['exchange_rate'],
                'is_default' => $request->boolean('is_default'),
            ]);
        });

        return redirect()->route('currencies.index')->with('success', 'Currency diperbarui.');
    }

    public function destroy(Currency $currency)
    {
        if ($currency->is_default) {
            return back()->with('error', 'Currency default tidak bisa dihapus.');
        }
        $currency->delete();

        return redirect()->route('currencies.index')->with('success', 'Currency dihapus.');
    }
}
