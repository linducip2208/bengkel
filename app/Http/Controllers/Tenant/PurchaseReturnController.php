<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
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
            ->whereIn('status', ['received', 'partially_returned'])
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
        if (! in_array($purchase->status, ['received', 'partially_returned'], true)) {
            return redirect()->route('purchases.return.index')
                ->with('error', 'Hanya purchase order dengan status "Diterima" yang dapat diretur.');
        }

        $purchase->load(['supplier', 'items.product.productType', 'items.product.unit', 'items.product.stockRecord']);

        return view('purchases.return', compact('purchase'));
    }

    public function store(Request $request, Purchase $purchase)
    {
        if (! in_array($purchase->status, ['received', 'partially_returned'], true)) {
            return redirect()->route('purchases.return.index')
                ->with('error', 'Hanya purchase order dengan status "Diterima" yang dapat diretur.');
        }

        $validated = $request->validate([
            'return_items' => ['required', 'array'],
            'return_items.*.product_id' => ['required', 'exists:products,id'],
            'return_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'return_reason' => ['required', 'string', 'max:500'],
        ]);

        // Sort for deterministic stock-lock order.
        usort($validated['return_items'], fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        try {
            $returnedAny = DB::transaction(function () use ($purchase, $validated) {
                // Lock + re-check status so a concurrent second submit aborts.
                $locked = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->first();
                if (! in_array($locked->status, ['received', 'partially_returned'], true)) {
                    throw new \RuntimeException('Purchase order ini sudah tidak dapat diretur.');
                }

                $returnedAny = false;
                $returnedValue = 0.0;

                foreach ($validated['return_items'] as $returnItem) {
                    $productId = (int) $returnItem['product_id'];
                    $returnQty = round((float) $returnItem['quantity'], 2);

                    $purchaseItems = $locked->items()->where('product_id', $productId);
                    $receivedQuantity = round((float) (clone $purchaseItems)->sum('quantity'), 2);
                    $unitPrice = (clone $purchaseItems)->value('unit_price');
                    $product = Product::withoutGlobalScopes()->find($productId);
                    if ($receivedQuantity <= 0 || $unitPrice === null || ! $product) {
                        continue;
                    }

                    $alreadyReturned = abs((float) $product->stockHistories()
                        ->where('type', 'return')
                        ->where('reference_type', Purchase::class)
                        ->where('reference_id', $locked->id)
                        ->sum('quantity_change'));
                    $returnable = round($receivedQuantity - $alreadyReturned, 2);
                    if ($returnQty > $returnable) {
                        throw new \RuntimeException("Jumlah retur {$product->name} melebihi sisa yang dapat diretur ({$returnable}).");
                    }

                    StockService::decrement(
                        $productId,
                        $returnQty,
                        'return',
                        'Retur PO #'.$locked->purchase_no.': '.$validated['return_reason'],
                        Purchase::class,
                        $locked->id,
                    );

                    $returnedValue += (float) $unitPrice * $returnQty;
                    $returnedAny = true;
                }

                if ($returnedAny) {
                    $hasRemaining = $locked->items->groupBy('product_id')->contains(function ($items, $productId) use ($locked) {
                        $returned = abs((float) $items->first()->product->stockHistories()
                            ->where('type', 'return')
                            ->where('reference_type', Purchase::class)
                            ->where('reference_id', $locked->id)
                            ->sum('quantity_change'));

                        return round((float) $items->sum('quantity') - $returned, 2) > 0;
                    });
                    $status = $hasRemaining ? 'partially_returned' : 'returned';
                    $locked->update(['status' => $status]);

                    $returnEvent = PurchaseHistoryRecord::create([
                        'purchase_id' => $locked->id,
                        'status' => $status,
                        'notes' => 'Retur diproses: '.$validated['return_reason'],
                        'changed_at' => now(),
                    ]);

                    // Reverse the inventory/AP posting of the original receive.
                    app(AutoJournalService::class)->journalPurchaseReturn($locked, round($returnedValue, 2), $returnEvent);
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
