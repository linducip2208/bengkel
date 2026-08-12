<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\StockTransfer;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::with('branch')->orderBy('name')->get();
        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        return view('warehouses.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $v = $request->validate(['name'=>'required','code'=>'required|unique:warehouses','branch_id'=>'nullable|exists:branches,id','address'=>'nullable']);
        Warehouse::create($v);
        return redirect()->route('warehouses.index')->with('success', 'Gudang ditambahkan.');
    }

    public function show(Warehouse $warehouse)
    {
        $stocks = WarehouseStock::with('product')->where('warehouse_id', $warehouse->id)->paginate(20);
        return view('warehouses.show', compact('warehouse', 'stocks'));
    }

    public function edit(Warehouse $warehouse) { $branches = Branch::all(); return view('warehouses.edit', compact('warehouse', 'branches')); }

    public function update(Request $request, Warehouse $warehouse)
    {
        $v = $request->validate(['name'=>'required','code'=>['required',Rule::unique('warehouses')->ignore($warehouse->id)],'branch_id'=>'nullable|exists:branches,id','address'=>'nullable']);
        $warehouse->update($v);
        return redirect()->route('warehouses.index')->with('success', 'Gudang diperbarui.');
    }

    public function destroy(Warehouse $warehouse) { $warehouse->delete(); return back()->with('success', 'Gudang dihapus.'); }

    // Stock Transfer
    public function transferIndex()
    {
        $transfers = StockTransfer::with(['fromWarehouse','toWarehouse'])->latest()->paginate(15);
        return view('warehouses.transfers', compact('transfers'));
    }

    public function transferCreate()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('warehouses.transfer-create', compact('warehouses', 'products'));
    }

    public function transferStore(Request $request)
    {
        $v = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
        ]);

        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-' . date('Ym') . '-' . str_pad(StockTransfer::count() + 1, 4, '0', STR_PAD_LEFT),
            'from_warehouse_id' => $v['from_warehouse_id'],
            'to_warehouse_id' => $v['to_warehouse_id'],
            'status' => 'completed',
            'created_by' => auth()->id(),
        ]);

        foreach ($v['items'] as $item) {
            $transfer->items()->create($item);
            // Kurangi stok dari gudang asal
            $fromStock = WarehouseStock::firstOrCreate(['warehouse_id'=>$v['from_warehouse_id'],'product_id'=>$item['product_id']], ['quantity'=>0]);
            $fromPrevious = $fromStock->quantity;
            $fromStock->decrement('quantity', $item['quantity']);
            StockHistory::create([
                'product_id' => $item['product_id'],
                'quantity_change' => -$item['quantity'],
                'previous_stock' => $fromPrevious,
                'new_stock' => $fromStock->quantity,
                'type' => 'transfer_out',
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
                'reason' => 'Transfer #' . $transfer->transfer_number,
                'user_id' => auth()->id(),
            ]);
            // Tambah stok ke gudang tujuan
            $toStock = WarehouseStock::firstOrCreate(['warehouse_id'=>$v['to_warehouse_id'],'product_id'=>$item['product_id']], ['quantity'=>0]);
            $toPrevious = $toStock->quantity;
            $toStock->increment('quantity', $item['quantity']);
            StockHistory::create([
                'product_id' => $item['product_id'],
                'quantity_change' => $item['quantity'],
                'previous_stock' => $toPrevious,
                'new_stock' => $toStock->quantity,
                'type' => 'transfer_in',
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
                'reason' => 'Transfer #' . $transfer->transfer_number,
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('warehouses.transfers')->with('success', 'Transfer stok berhasil.');
    }
}
