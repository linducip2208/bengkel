<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InvoiceScheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceSchemeController extends Controller
{
    public function index(Request $request)
    {
        $query = InvoiceScheme::query()->with('branch');
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('prefix', 'like', "%{$request->search}%")
                    ->orWhere('format', 'like', "%{$request->search}%");
            });
        }
        $schemes = $query->orderByDesc('is_default')->orderBy('name')->paginate(15)->withQueryString();
        return view('invoice-schemes.index', compact('schemes'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        return view('invoice-schemes.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        DB::transaction(function () use ($validated, $request) {
            if ($request->boolean('is_default')) {
                InvoiceScheme::where('is_default', true)->update(['is_default' => false]);
            }
            InvoiceScheme::create([
                'name' => $validated['name'],
                'prefix' => $validated['prefix'],
                'format' => $validated['format'],
                'start_number' => (int) ($validated['start_number'] ?? 1),
                'next_number' => (int) ($validated['next_number'] ?? $validated['start_number'] ?? 1),
                'branch_id' => $validated['branch_id'] ?? null,
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('invoice-schemes.index')->with('success', 'Skema penomoran berhasil ditambahkan.');
    }

    public function edit(InvoiceScheme $invoiceScheme)
    {
        $branches = Branch::orderBy('name')->get();
        return view('invoice-schemes.edit', compact('invoiceScheme', 'branches'));
    }

    public function update(Request $request, InvoiceScheme $invoiceScheme)
    {
        $validated = $this->validateData($request);

        DB::transaction(function () use ($validated, $invoiceScheme, $request) {
            if ($request->boolean('is_default')) {
                InvoiceScheme::where('id', '!=', $invoiceScheme->id)->where('is_default', true)->update(['is_default' => false]);
            }
            $invoiceScheme->update([
                'name' => $validated['name'],
                'prefix' => $validated['prefix'],
                'format' => $validated['format'],
                'start_number' => (int) ($validated['start_number'] ?? $invoiceScheme->start_number),
                'next_number' => (int) ($validated['next_number'] ?? $invoiceScheme->next_number),
                'branch_id' => $validated['branch_id'] ?? null,
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('invoice-schemes.index')->with('success', 'Skema penomoran berhasil diperbarui.');
    }

    public function destroy(InvoiceScheme $invoiceScheme)
    {
        $invoiceScheme->delete();
        return redirect()->route('invoice-schemes.index')->with('success', 'Skema penomoran berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:50',
            'format' => 'required|string|max:100',
            'start_number' => 'nullable|integer|min:1',
            'next_number' => 'nullable|integer|min:1',
            'branch_id' => 'nullable|exists:branches,id',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
