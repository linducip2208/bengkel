<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Printer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrinterController extends Controller
{
    public function index(Request $request)
    {
        $query = Printer::query()->with('branch');
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('ip_address', 'like', "%{$request->search}%");
            });
        }
        $printers = $query->orderByDesc('is_default')->orderBy('name')->paginate(15)->withQueryString();
        return view('printers.index', compact('printers'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        return view('printers.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['thermal', 'label', 'a4'])],
            'ip_address' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'is_default' => 'nullable|boolean',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            if ($request->boolean('is_default')) {
                Printer::where('is_default', true)->update(['is_default' => false]);
            }
            Printer::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'ip_address' => $validated['ip_address'] ?? null,
                'port' => $validated['port'] ?? 9100,
                'is_default' => $request->boolean('is_default'),
                'branch_id' => $validated['branch_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('printers.index')->with('success', 'Printer berhasil ditambahkan.');
    }

    public function edit(Printer $printer)
    {
        $branches = Branch::orderBy('name')->get();
        return view('printers.edit', compact('printer', 'branches'));
    }

    public function update(Request $request, Printer $printer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['thermal', 'label', 'a4'])],
            'ip_address' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'is_default' => 'nullable|boolean',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $printer, $request) {
            if ($request->boolean('is_default')) {
                Printer::where('id', '!=', $printer->id)->where('is_default', true)->update(['is_default' => false]);
            }
            $printer->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'ip_address' => $validated['ip_address'] ?? null,
                'port' => $validated['port'] ?? 9100,
                'is_default' => $request->boolean('is_default'),
                'branch_id' => $validated['branch_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('printers.index')->with('success', 'Printer berhasil diperbarui.');
    }

    public function destroy(Printer $printer)
    {
        $printer->delete();
        return redirect()->route('printers.index')->with('success', 'Printer berhasil dihapus.');
    }
}
