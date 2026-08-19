<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Imports\ProductsImport;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPrice;
use App\Services\ProductService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $products = $this->productService->index($request->only([
            'search', 'product_type_id', 'supplier_id', 'stock_status'
        ]));

        $productTypes = ProductType::orderBy('type')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('products.index', compact('products', 'productTypes', 'suppliers'));
    }

    public function create()
    {
        $productTypes = ProductType::orderBy('type')->get();
        $units = ProductUnit::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $prefix = 'PRD-' . date('Ym');
        $lastProduct = Product::withTrashed()->where('product_no', 'like', $prefix . '%')->orderByDesc('id')->first();
        $nextProductNo = $prefix . '-' . str_pad(
            $lastProduct ? (int) substr($lastProduct->product_no, -4) + 1 : 1,
            4, '0', STR_PAD_LEFT
        );

        return view('products.create', compact('productTypes', 'units', 'suppliers', 'nextProductNo'));
    }

    public function store(ProductRequest $request)
    {
        $this->productService->create($request->validated());

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load(['productType', 'unit', 'supplier', 'stockRecord', 'stockHistories' => function ($q) {
            $q->orderByDesc('created_at')->limit(50);
        }]);

        $purchaseHistory = $product->purchaseItems()
            ->with('purchase.supplier')
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'purchase_page');

        return view('products.show', compact('product', 'purchaseHistory'));
    }

    public function printBarcode(Product $product)
    {
        return view('products.barcode', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('stockRecord');
        $productTypes = ProductType::orderBy('type')->get();
        $units = ProductUnit::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('products.edit', compact('product', 'productTypes', 'units', 'suppliers'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->productService->update($product, $request->validated());

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    public function importForm()
    {
        $productTypes = ProductType::orderBy('type')->get();
        $units = ProductUnit::orderBy('name')->get();

        return view('products.import', compact('productTypes', 'units'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new ProductsImport();
        Excel::import($import, $request->file('file'));

        $failed = count($import->errors);
        $message = "{$import->imported} produk berhasil diimport."
            . ($failed > 0 ? " {$failed} baris gagal." : '');

        return redirect()->route('products.index')
            ->with('success', $message)
            ->with('import_errors', $import->errors);
    }

    public function stockAdjust(Request $request, Product $product)
    {
        $product->load('stockRecord');

        if ($request->isMethod('get')) {
            return view('products.stock-adjust', compact('product'));
        }

        $request->validate([
            'adjustment_type' => ['required', 'in:add,reduce,set'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $type = $request->adjustment_type;
        $quantity = (int) $request->quantity;
        $reason = $request->reason;

        if ($type === 'add') {
            $this->productService->adjustStock($product, $quantity, $reason);
        } elseif ($type === 'reduce') {
            $this->productService->adjustStock($product, -$quantity, $reason);
        } elseif ($type === 'set') {
            $this->productService->setStock($product, $quantity, $reason);
        }

        if ($request->filled('minimum_stock') && $product->stockRecord) {
            $product->stockRecord->update(['minimum_stock' => (int) $request->minimum_stock]);
        }

        return redirect()->route('products.show', $product)
            ->with('success', 'Stok berhasil disesuaikan.');
    }

    public function stockOpname(Request $request)
    {
        if ($request->isMethod('get')) {
            $products = Product::with(['productType', 'unit', 'stockRecord'])->orderBy('name')->get();

            return view('products.stock-opname', compact('products'));
        }

        $request->validate([
            'products' => ['required', 'array'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.physical_stock' => ['required', 'integer', 'min:0'],
        ]);

        $updated = 0;
        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            if ($product && (int) $item['physical_stock'] !== $product->current_stock) {
                $this->productService->setStock(
                    $product,
                    (int) $item['physical_stock'],
                    'Hasil stock opname'
                );
                $updated++;
            }
        }

        return redirect()->route('products.index')
            ->with('success', "Stock opname selesai. {$updated} produk disesuaikan.");
    }

    public function searchJson(Request $request)
    {
        $search = $request->get('q');
        $products = Product::with('stockRecord')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'code', 'name', 'price']);

        $products->each(function ($p) {
            $p->current_stock = $p->stockRecord?->quantity ?? 0;
            $p->stock_status = $p->stockRecord?->quantity > 0 ? 'in_stock' : 'out';
        });

        return response()->json($products);
    }

    public function reorderSuggestions()
    {
        $suggestions = app(ReportService::class)->getReorderSuggestions();

        return view('products.reorder', compact('suggestions'));
    }

    public function createReorderPo(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $product = Product::with('stockRecord')->findOrFail($request->product_id);

        $cheapest = SupplierPrice::with('supplier')
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->orderBy('price')
            ->first();

        if (!$cheapest) {
            return back()->with('error', 'Tidak ada harga supplier untuk produk ini.');
        }

        $stockRecord = $product->stockRecord;
        $minStock = $stockRecord?->minimum_stock ?? 5;
        $currentStock = $stockRecord?->quantity ?? 0;
        $quantity = max(($minStock * 2) - $currentStock, 1);

        $unitPrice = (float) $cheapest->price;

        $purchaseOrder = DB::transaction(function () use ($product, $cheapest, $quantity, $unitPrice) {
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'supplier_id' => $cheapest->supplier_id,
                'branch_id' => $product->branch_id,
                'order_date' => now()->toDateString(),
                'status' => 'draft',
                'subtotal' => $unitPrice * $quantity,
                'tax_amount' => 0,
                'grand_total' => $unitPrice * $quantity,
                'notes' => 'Auto PO dari rekomendasi reorder.',
                'created_by' => auth()->id(),
            ]);

            $purchaseOrder->items()->create([
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
            ]);

            return $purchaseOrder;
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Draft purchase order ' . $purchaseOrder->po_number . ' dibuat otomatis dengan supplier termurah.');
    }

    private function generatePoNumber(): string
    {
        $prefix = 'PO-' . date('Ymd');
        $last = PurchaseOrder::where('po_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();
        $next = $last ? (int) substr($last->po_number, -4) + 1 : 1;

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
