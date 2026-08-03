<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        private PurchaseService $purchaseService
    ) {}

    public function index(Request $request)
    {
        $purchases = $this->purchaseService->index($request->only([
            'search', 'status', 'supplier_id', 'date_from', 'date_to'
        ]));

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.index', compact('purchases', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $purchaseNo = $this->generateDraftPurchaseNo();

        return view('purchases.create', compact('suppliers', 'purchaseNo'));
    }

    public function store(PurchaseRequest $request)
    {
        $this->purchaseService->create($request->validated());

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase order berhasil dibuat.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product.productType', 'items.product.unit', 'creator', 'historyRecords']);

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Hanya purchase order dengan status Draft yang dapat diedit.');
        }

        $purchase->load(['items.product']);
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.edit', compact('purchase', 'suppliers'));
    }

    public function update(PurchaseRequest $request, Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Hanya purchase order dengan status Draft yang dapat diperbarui.');
        }

        $this->purchaseService->update($purchase, $request->validated());

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase order berhasil diperbarui.');
    }

    public function destroy(Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.index')
                ->with('error', 'Hanya purchase order dengan status Draft yang dapat dihapus.');
        }

        $purchase->delete();

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase order berhasil dihapus.');
    }

    public function markReceived(Purchase $purchase)
    {
        try {
            $this->purchaseService->markReceived($purchase);
        } catch (\RuntimeException $e) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Stok berhasil ditambahkan. Status PO diubah menjadi Diterima.');
    }

    private function generateDraftPurchaseNo(): string
    {
        $prefix = 'PO-' . date('Ymd');
        $last = Purchase::where('purchase_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();
        $next = $last ? (int) substr($last->purchase_no, -4) + 1 : 1;

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
