<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InvoiceItem;
use App\Models\WarrantyClaim;
use Illuminate\Http\Request;

class WarrantyClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = WarrantyClaim::with(['customer', 'invoiceItem.product', 'invoiceItem.invoice']);
        if ($request->filled('status')) $query->where('status', $request->status);
        $claims = $query->latest()->paginate(20)->withQueryString();
        return view('warranty-claims.index', compact('claims'));
    }

    public function create()
    {
        $items = InvoiceItem::with(['product', 'invoice.customer'])
            ->whereHas('product', fn($q) => $q->whereNotNull('warranty')->where('warranty', '!=', ''))
            ->latest()
            ->limit(200)
            ->get();
        return view('warranty-claims.create', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_item_id' => 'required|exists:invoice_items,id',
            'claim_date' => 'required|date',
            'complaint' => 'required|string|max:1000',
        ]);

        $item = InvoiceItem::with('invoice')->findOrFail($validated['invoice_item_id']);
        $claim = WarrantyClaim::create($validated + [
            'customer_id' => $item->invoice->customer_id,
            'status' => 'submitted',
        ]);

        ActivityLog::record('warranty.create', $claim, "Klaim garansi untuk {$item->description}");
        return redirect()->route('warranty-claims.index')->with('success', 'Klaim garansi dibuat.');
    }

    public function update(Request $request, WarrantyClaim $warrantyClaim)
    {
        $validated = $request->validate([
            'status' => 'required|in:submitted,approved,rejected,resolved',
            'resolution' => 'nullable|string|max:1000',
        ]);
        $warrantyClaim->update($validated);
        ActivityLog::record('warranty.update', $warrantyClaim, "Update klaim ke {$validated['status']}");
        return back()->with('success', 'Status klaim diperbarui.');
    }

    public function destroy(WarrantyClaim $warrantyClaim)
    {
        ActivityLog::record('warranty.delete', $warrantyClaim, "Hapus klaim {$warrantyClaim->id}");
        $warrantyClaim->delete();
        return back()->with('success', 'Klaim dihapus.');
    }
}
