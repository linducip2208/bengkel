<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PettyCash;
use Illuminate\Http\Request;

class PettyCashController extends Controller
{
    public function index(Request $request)
    {
        $query = PettyCash::with('creator');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        $rows = $query->latest('date')->paginate(30)->withQueryString();

        $balance = (float) PettyCash::where('type', 'in')->sum('amount')
                 + (float) PettyCash::where('type', 'replenish')->sum('amount')
                 - (float) PettyCash::where('type', 'out')->sum('amount');

        return view('petty-cash.index', compact('rows', 'balance'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:in,out,replenish',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);
        $validated['created_by'] = auth()->id();
        $tx = PettyCash::create($validated);
        ActivityLog::record('petty_cash.create', $tx, "Petty cash {$validated['type']}: {$validated['description']}");

        return back()->with('success', 'Transaksi kas kecil tercatat.');
    }

    public function destroy(PettyCash $pettyCash)
    {
        ActivityLog::record('petty_cash.delete', $pettyCash, "Hapus petty cash {$pettyCash->description}");
        $pettyCash->delete();

        return back()->with('success', 'Transaksi dihapus.');
    }
}
