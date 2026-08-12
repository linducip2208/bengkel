<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    public function index(Request $request): View
    {
        $sales = Sale::query()
            ->with(['customer', 'items'])
            ->withCount('items')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"))
                        ->orWhere('sales_no', 'like', "%{$request->search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('stockRecord')->orderBy('name')->get();

        return view('sales.create', compact('customers', 'products'));
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['total_amount'] = $this->lineItemsTotal($items);
        $data['tax_amount'] = $data['tax_amount'] ?? 0;

        try {
            $sale = DB::transaction(function () use ($data, $items) {
                $sale = $this->saleService->create($data);
                $this->syncItems($sale, $items);

                return $sale;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Penjualan sparepart berhasil dicatat.');
    }

    public function show(Sale $sale): View
    {
        $sale->load(['customer', 'items.product', 'invoices.paymentRecords']);

        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale): View
    {
        $sale->load('items.product');
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('stockRecord')->orderBy('name')->get();

        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    public function update(SaleRequest $request, Sale $sale): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['total_amount'] = $this->lineItemsTotal($items);
        $data['tax_amount'] = $data['tax_amount'] ?? 0;
        $data['grand_total'] = $data['total_amount'] + $data['tax_amount'];

        try {
            DB::transaction(function () use ($sale, $data, $items) {
                $sale->update($data);
                $this->syncItems($sale, $items, true);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Penjualan sparepart berhasil diperbarui.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $sale->delete();

        return redirect()->route('sales.index')
            ->with('success', 'Penjualan berhasil dihapus.');
    }

    protected function lineItemsTotal(array $items): float
    {
        return round(collect($items)->sum(fn($i) => (float) $i['quantity'] * (float) $i['unit_price']), 2);
    }

    protected function syncItems(Sale $sale, array $items, bool $isUpdate = false): void
    {
        if ($isUpdate) {
            foreach ($sale->items as $old) {
                $this->restoreStock($old->product_id, $old->quantity, $sale);
            }
            $sale->items()->delete();
        }

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
            ]);

            $this->reduceStock($item['product_id'], $quantity, $sale);
        }
    }

    protected function reduceStock(int $productId, int $quantity, Sale $sale): void
    {
        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $productId)->first();
        if (!$stock) {
            return;
        }

        if ($stock->quantity < $quantity) {
            $product = Product::withoutGlobalScopes()->find($productId);
            $name = $product?->name ?? "ID {$productId}";
            throw new \RuntimeException("Stok \"{$name}\" tidak cukup: tersedia {$stock->quantity}, dibutuhkan {$quantity}.");
        }

        $previous = $stock->quantity;
        $stock->decrement('quantity', $quantity);

        StockHistory::create([
            'product_id' => $productId,
            'quantity_change' => -$quantity,
            'previous_stock' => $previous,
            'new_stock' => $previous - $quantity,
            'type' => 'sale',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'reason' => 'Penjualan ' . $sale->sales_no,
            'user_id' => auth()->id() ?? 1,
        ]);
    }

    protected function restoreStock(int $productId, int $quantity, Sale $sale): void
    {
        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $productId)->first();
        if (!$stock) {
            return;
        }

        $previous = $stock->quantity;
        $stock->increment('quantity', $quantity);

        StockHistory::create([
            'product_id' => $productId,
            'quantity_change' => $quantity,
            'previous_stock' => $previous,
            'new_stock' => $previous + $quantity,
            'type' => 'sale_restore',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'reason' => 'Koreksi penjualan ' . $sale->sales_no,
            'user_id' => auth()->id() ?? 1,
        ]);
    }
}
