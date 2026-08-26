<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\AutoJournalService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $adjustments = StockAdjustment::with(['product', 'warehouse', 'requestedBy', 'approvedBy'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('product_search'), function ($q) use ($request) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->product_search.'%'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('stock-adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $products = Product::with('stockRecord')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('stock-adjustments.create', compact('products', 'warehouses', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'new_quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $product = Product::with('stockRecord')->findOrFail($request->product_id);
        $previousQuantity = $product->current_stock;
        $newQuantity = (int) $request->new_quantity;
        $quantityChange = $newQuantity - $previousQuantity;

        StockAdjustment::create([
            'product_id' => $product->id,
            'warehouse_id' => $request->warehouse_id,
            'branch_id' => $request->branch_id,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'quantity_change' => $quantityChange,
            'reason' => $request->reason,
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Stock adjustment request dibuat. Menunggu approval.');
    }

    public function approve($id)
    {
        $this->authorize('stock-adjustments.approve');

        $adjustment = StockAdjustment::where('status', 'pending')->findOrFail($id);

        $applied = DB::transaction(function () use ($adjustment) {
            // Lock + re-check: a concurrent second approval aborts here.
            $locked = StockAdjustment::where('status', 'pending')
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return null;
            }

            // The request snapshot may be stale if stock moved since it was
            // filed — refuse rather than blindly overwrite current stock.
            $product = Product::with(['stockRecord' => fn ($q) => $q->withoutGlobalScopes()])
                ->withoutGlobalScopes()
                ->findOrFail($locked->product_id);
            $currentStock = (int) ($product->stockRecord->quantity ?? 0);
            $snapshotStock = (int) $locked->previous_quantity;

            if ($currentStock !== $snapshotStock) {
                throw new \RuntimeException(
                    "Stok saat ini ({$currentStock}) berbeda dari saat pengajuan ({$snapshotStock}). Tolak pengajuan ini dan buat penyesuaian baru."
                );
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            StockService::set(
                $product->id,
                (int) $locked->new_quantity,
                'adjustment',
                'Adjustment approved: '.$locked->reason,
                StockAdjustment::class,
                $locked->id,
            );

            app(AutoJournalService::class)->journalStockAdjustment(
                $product->id,
                (float) $locked->quantity_change,
                (float) ($product->cost_price ?? 0),
                $locked->reason,
                StockAdjustment::class,
                $locked->id,
            );

            ActivityLog::record('stock-adjustment.approve', $locked, "Penyesuaian stok {$product->name}: {$locked->previous_quantity} → {$locked->new_quantity}");

            return true;
        });

        if ($applied === null) {
            return redirect()->route('stock-adjustments.index')
                ->with('error', 'Pengajuan sudah diproses oleh user lain.');
        }

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Stock adjustment approved. Stok sudah diperbarui.');
    }

    public function reject(Request $request, $id)
    {
        $this->authorize('stock-adjustments.approve');

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $updated = StockAdjustment::where('status', 'pending')
            ->whereKey($id)
            ->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        if (! $updated) {
            return redirect()->route('stock-adjustments.index')
                ->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        ActivityLog::record('stock-adjustment.reject', null, "Pengajuan adjustment #{$id} ditolak: {$request->rejection_reason}");

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Stock adjustment ditolak.');
    }
}
