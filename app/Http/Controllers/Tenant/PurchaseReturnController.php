<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Purchase;
use App\Models\PurchaseHistoryRecord;
use App\Models\Supplier;
use App\Services\AutoJournalService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'items'])
            ->where('status', 'received')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('purchase_no', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            })
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.return-index', compact('purchases', 'suppliers'));
    }

    public function create(Purchase $purchase)
    {
        if ($purchase->status !== 'received') {
            return redirect()->route('purchases.return.index')
                ->with('error', 'Hanya purchase order dengan status "Diterima" yang dapat diretur.');
        }

        $purchase->load(['supplier', 'items.product.productType', 'items.product.unit', 'items.product.stockRecord']);

        return view('purchases.return', compact('purchase'));
    }

    public function store(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== 'received') {
            return redirect()->route('purchases.return.index')
                ->with('error', 'Hanya purchase order dengan status "Diterima" yang dapat diretur.');
        }

        $validated = $request->validate([
            'return_items' => ['required', 'array'],
            'return_items.*.product_id' => ['required', 'exists:products,id'],
            'return_items.*.quantity' => ['required', 'integer', 'min:1'],
            'return_reason' => ['required', 'string', 'max:500'],
        ]);

        // Sort for deterministic stock-lock order.
        usort($validated['return_items'], fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        try {
            $returnedAny = DB::transaction(function () use ($purchase, $validated) {
                // Lock + re-check status so a concurrent second submit aborts.
                $locked = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->first();
                if ($locked->status !== 'received') {
                    throw new \RuntimeException('Purchase order ini sudah tidak dapat diretur.');
                }

                $returnedAny = false;
                $returnedValue = 0.0;

                foreach ($validated['return_items'] as $returnItem) {
                    $productId = (int) $returnItem['product_id'];
                    $returnQty = (int) $returnItem['quantity'];

                    $purchaseItem = $locked->items()->where('product_id', $productId)->first();
                    if (! $purchaseItem) {
                        continue;
                    }

                    $maxReturn = (int) $purchaseItem->quantity;
                    $returnQty = min($returnQty, $maxReturn);
                    if ($returnQty <= 0) {
                        continue;
                    }

                    StockService::decrement(
                        $productId,
                        $returnQty,
                        'return',
                        'Retur PO #'.$locked->purchase_no.': '.$validated['return_reason'],
                        Purchase::class,
                        $locked->id,
                    );

                    $returnedValue += (float) $purchaseItem->unit_price * $returnQty;
                    $returnedAny = true;
                }

                if ($returnedAny) {
                    $locked->update(['status' => 'returned']);

                    PurchaseHistoryRecord::create([
                        'purchase_id' => $locked->id,
                        'status' => 'returned',
                        'notes' => 'Retur diproses: '.$validated['return_reason'],
                        'changed_at' => now(),
                    ]);

                    // Reverse the inventory/AP posting of the original receive.
                    app(AutoJournalService::class)->journalPurchaseReturn($locked, round($returnedValue, 2));
                }

                return $returnedAny;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! $returnedAny) {
            return back()->with('error', 'Tidak ada item yang valid untuk diretur.');
        }

        ActivityLog::record('purchase.return', $purchase, "Retur pembelian {$purchase->purchase_no}");

        return redirect()->route('purchases.return.index')
            ->with('success', 'Retur pembelian berhasil diproses.');
    }
}
