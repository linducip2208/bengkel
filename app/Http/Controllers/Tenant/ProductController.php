<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Services\ProductService;
use Illuminate\Http\Request;

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
        $nextProductNo = 'PRD-' . date('Ym') . '-' . str_pad(
            Product::withTrashed()->where('product_no', 'like', 'PRD-' . date('Ym') . '%')->count() + 1,
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

    public function import(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('products.import');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                $rows[] = array_combine($header, $row);
            }
        }
        fclose($handle);

        $result = $this->productService->bulkImport($rows);

        return redirect()->route('products.index')
            ->with('success', "{$result['imported']} produk berhasil diimport.")
            ->with('import_errors', $result['errors']);
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
}
