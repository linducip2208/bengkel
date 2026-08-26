<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AutoJournalService;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'items'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('po_number', 'like', "%{$request->search}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$request->search}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchase-orders.index', compact('purchaseOrders', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $poNumber = $this->generatePoNumber();

        return view('purchase-orders.create', compact('suppliers', 'branches', 'poNumber'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $purchaseOrder = DB::transaction(function () use ($validated) {
            $items = $validated['items'];
            unset($validated['items']);

            $subtotal = $this->sumSubtotal($items);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);

            $purchaseOrder = PurchaseOrder::create(array_merge($validated, [
                'po_number' => $this->generatePoNumber(),
                'subtotal' => $subtotal,
                'grand_total' => $subtotal + $taxAmount,
                'created_by' => auth()->id(),
            ]));

            $this->createItems($purchaseOrder, $items);

            return $purchaseOrder;
        });

        ActivityLog::record('purchase-order.create', $purchaseOrder, "Purchase order {$purchaseOrder->po_number} dibuat");

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order '.$purchaseOrder->po_number.' berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'branch', 'items.product', 'creator']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Hanya purchase order dengan status Draft yang dapat diedit.');
        }

        $purchaseOrder->load(['items.product']);
        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'branches'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Hanya purchase order dengan status Draft yang dapat diperbarui.');
        }

        $validated = $this->validateData($request);

        DB::transaction(function () use ($purchaseOrder, $validated) {
            $items = $validated['items'];
            unset($validated['items']);

            $subtotal = $this->sumSubtotal($items);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);

            $purchaseOrder->update(array_merge($validated, [
                'subtotal' => $subtotal,
                'grand_total' => $subtotal + $taxAmount,
            ]));

            $purchaseOrder->items()->delete();
            $this->createItems($purchaseOrder, $items);
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order berhasil diperbarui.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if (! in_array($purchaseOrder->status, ['draft', 'cancelled'])) {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Hanya purchase order dengan status Draft/Cancelled yang dapat dihapus.');
        }

        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order berhasil dihapus.');
    }

    public function markReceived(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->items()->whereNull('product_id')->exists()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Ada item tanpa produk terkait. Penerimaan hanya bisa dilakukan jika semua item terhubung ke produk.');
        }

        try {
            $purchase = DB::transaction(function () use ($purchaseOrder) {
                // Guard + lock inside the transaction (concurrent double-receive aborts).
                $locked = PurchaseOrder::query()->whereKey($purchaseOrder->id)->lockForUpdate()->first();
                if ($locked->status === 'received') {
                    throw new \RuntimeException('Purchase order sudah diterima.');
                }

                $purchase = Purchase::create([
                    'purchase_no' => DocumentNumberService::generate(DocumentNumberService::PURCHASES, 'PO', 'Ymd', 4),
                    'supplier_id' => $locked->supplier_id,
                    'purchase_date' => now()->toDateString(),
                    'status' => 'received',
                    'total_amount' => $locked->grand_total,
                    'notes' => 'Diterima dari purchase order #'.$locked->po_number,
                    'created_by' => auth()->id(),
                    'branch_id' => $locked->branch_id,
                ]);

                foreach ($locked->items as $item) {
                    $purchase->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => (int) round($item->quantity),
                        'unit_price' => round((float) $item->unit_price, 2),
                        'total_price' => round((float) $item->total_price, 2),
                    ]);

                    StockService::increment(
                        (int) $item->product_id,
                        (float) $item->quantity,
                        'purchase',
                        "Purchase #{$purchase->purchase_no}",
                        Purchase::class,
                        $purchase->id,
                    );
                }

                $locked->update(['status' => 'received']);

                return $purchase;
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', $e->getMessage());
        }

        try {
            app(AutoJournalService::class)->journalPurchase($purchase);
        } catch (\Throwable $e) {
            Log::warning("PO auto-journal: {$e->getMessage()}");
        }

        ActivityLog::record('purchase-order.receive', $purchaseOrder, "Purchase order {$purchaseOrder->po_number} diterima menjadi purchase {$purchase->purchase_no}");

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Barang diterima. Purchase order diubah menjadi purchase & stok bertambah.');
    }

    private function validateData(Request $request): array
    {
        $this->normalizeItems($request);

        return $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'nullable|exists:branches,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'status' => ['required', Rule::in(['draft', 'sent', 'received', 'cancelled'])],
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
    }

    private function normalizeItems(Request $request): void
    {
        $data = $request->all();

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $i => $item) {
                if (empty($item['product_id'])) {
                    $data['items'][$i]['product_id'] = null;
                }
            }
        }

        $request->merge($data);
    }

    private function sumSubtotal(array $items): float
    {
        return (float) collect($items)->sum(function ($item) {
            return (float) $item['quantity'] * (float) $item['unit_price'];
        });
    }

    private function createItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        foreach ($items as $item) {
            $purchaseOrder->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => (float) $item['quantity'] * (float) $item['unit_price'],
            ]);
        }
    }

    private function generatePoNumber(): string
    {
        return DocumentNumberService::generate(DocumentNumberService::PURCHASE_ORDERS, 'PO', 'Ymd', 4);
    }

    private function generatePurchaseNo(): string
    {
        // Unified with PurchaseService so purchase_no sequences never fork.
        return DocumentNumberService::generate(DocumentNumberService::PURCHASES, 'PO', 'Ymd', 4);
    }
}
