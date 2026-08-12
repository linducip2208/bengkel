<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseHistoryRecord;
use App\Models\StockHistory;
use App\Models\StockRecord;
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
                $q->where('purchase_no', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            })
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = \App\Models\Supplier::orderBy('name')->get();

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

        DB::transaction(function () use ($purchase, $validated) {
            $returnedAny = false;

            foreach ($validated['return_items'] as $returnItem) {
                $productId = $returnItem['product_id'];
                $returnQty = (int) $returnItem['quantity'];

                $purchaseItem = $purchase->items()->where('product_id', $productId)->first();
                if (!$purchaseItem) {
                    continue;
                }

                $maxReturn = $purchaseItem->quantity;
                if ($returnQty > $maxReturn) {
                    $returnQty = $maxReturn;
                }
                if ($returnQty <= 0) {
                    continue;
                }

                $product = \App\Models\Product::find($productId);
                if (!$product) {
                    continue;
                }

                $stockRecord = StockRecord::firstOrCreate(
                    ['product_id' => $productId],
                    [
                        'supplier_id' => $product->supplier_id,
                        'quantity' => 0,
                        'minimum_stock' => 0,
                        'rack_location' => null,
                    ]
                );

                $previousStock = $stockRecord->quantity;
                $newStock = max(0, $previousStock - $returnQty);

                $stockRecord->update(['quantity' => $newStock]);

                StockHistory::create([
                    'product_id' => $productId,
                    'quantity_change' => -$returnQty,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'type' => 'return',
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'reason' => 'Retur PO #' . $purchase->purchase_no . ': ' . $validated['return_reason'],
                    'user_id' => auth()->id(),
                ]);

                $returnedAny = true;
            }

            if ($returnedAny) {
                $purchase->update(['status' => 'returned']);

                PurchaseHistoryRecord::create([
                    'purchase_id' => $purchase->id,
                    'status' => 'returned',
                    'notes' => 'Retur diproses: ' . $validated['return_reason'],
                    'changed_at' => now(),
                ]);
            }
        });

        if (!$returnedAny) {
            return back()->with('error', 'Tidak ada item yang valid untuk diretur.');
        }

        return redirect()->route('purchases.return.index')
            ->with('success', 'Retur pembelian berhasil diproses.');
    }
}
