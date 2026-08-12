<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $adjustments = StockAdjustment::with(['product', 'warehouse', 'requestedBy', 'approvedBy'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('product_search'), function ($q) use ($request) {
                $q->whereHas('product', fn($p) => $p->where('name', 'like', '%' . $request->product_search . '%'));
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
        $adjustment = StockAdjustment::where('status', 'pending')->findOrFail($id);

        DB::transaction(function () use ($adjustment) {
            $adjustment->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $product = Product::with('stockRecord')->findOrFail($adjustment->product_id);
            $previousStock = $product->current_stock;
            $newStock = $adjustment->new_quantity;

            if ($product->stockRecord) {
                $product->stockRecord->update(['quantity' => $newStock]);
            } else {
                \App\Models\StockRecord::create([
                    'product_id' => $product->id,
                    'supplier_id' => $product->supplier_id,
                    'quantity' => $newStock,
                    'minimum_stock' => 0,
                ]);
            }

            StockHistory::create([
                'product_id' => $product->id,
                'quantity_change' => $adjustment->quantity_change,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'type' => 'adjustment',
                'reason' => 'Adjustment approved: ' . $adjustment->reason,
                'reference_type' => StockAdjustment::class,
                'reference_id' => $adjustment->id,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Stock adjustment approved. Stok sudah diperbarui.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $adjustment = StockAdjustment::where('status', 'pending')->findOrFail($id);

        $adjustment->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Stock adjustment ditolak.');
    }
}
