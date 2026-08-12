<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $salesOrders = SalesOrder::query()
            ->with(['customer', 'items'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$request->search}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('sales-orders.index', compact('salesOrders'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $vehicles = Vehicle::with('customer')->orderBy('number_plate')->get();
        $branches = Branch::orderBy('name')->get();
        $orderNumber = $this->generateOrderNumber();

        return view('sales-orders.create', compact('customers', 'vehicles', 'branches', 'orderNumber'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $salesOrder = DB::transaction(function () use ($validated) {
            $items = $validated['items'];
            unset($validated['items']);

            $subtotal = $this->sumSubtotal($items);
            $discount = (float) ($validated['discount'] ?? 0);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);

            $salesOrder = SalesOrder::create(array_merge($validated, [
                'order_number' => $this->generateOrderNumber(),
                'subtotal' => $subtotal,
                'grand_total' => $subtotal + $taxAmount - $discount,
                'created_by' => auth()->id(),
            ]));

            $this->createItems($salesOrder, $items);

            return $salesOrder;
        });

        ActivityLog::record('sales-order.create', $salesOrder, "Sales order {$salesOrder->order_number} dibuat");

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order ' . $salesOrder->order_number . ' berhasil dibuat.');
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'vehicle', 'branch', 'items.product', 'creator']);

        return view('sales-orders.show', compact('salesOrder'));
    }

    public function edit(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Hanya sales order dengan status Draft yang dapat diedit.');
        }

        $salesOrder->load(['items.product']);
        $customers = Customer::orderBy('name')->get();
        $vehicles = Vehicle::with('customer')->orderBy('number_plate')->get();
        $branches = Branch::orderBy('name')->get();

        return view('sales-orders.edit', compact('salesOrder', 'customers', 'vehicles', 'branches'));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Hanya sales order dengan status Draft yang dapat diperbarui.');
        }

        $validated = $this->validateData($request);

        DB::transaction(function () use ($salesOrder, $validated) {
            $items = $validated['items'];
            unset($validated['items']);

            $subtotal = $this->sumSubtotal($items);
            $discount = (float) ($validated['discount'] ?? 0);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);

            $salesOrder->update(array_merge($validated, [
                'subtotal' => $subtotal,
                'grand_total' => $subtotal + $taxAmount - $discount,
            ]));

            $salesOrder->items()->delete();
            $this->createItems($salesOrder, $items);
        });

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order berhasil diperbarui.');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if (!in_array($salesOrder->status, ['draft', 'rejected'])) {
            return redirect()->route('sales-orders.index')
                ->with('error', 'Hanya sales order dengan status Draft/Rejected yang dapat dihapus.');
        }

        $salesOrder->delete();

        return redirect()->route('sales-orders.index')
            ->with('success', 'Sales order berhasil dihapus.');
    }

    public function approve(SalesOrder $salesOrder)
    {
        $salesOrder->update(['status' => 'approved']);
        ActivityLog::record('sales-order.approve', $salesOrder, "Sales order {$salesOrder->order_number} disetujui");

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order disetujui.');
    }

    public function reject(SalesOrder $salesOrder)
    {
        $salesOrder->update(['status' => 'rejected']);
        ActivityLog::record('sales-order.reject', $salesOrder, "Sales order {$salesOrder->order_number} ditolak");

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order ditolak.');
    }

    public function convertToInvoice(SalesOrder $salesOrder)
    {
        if ($salesOrder->status === 'converted') {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Sales order sudah dikonversi menjadi invoice.');
        }

        if ($salesOrder->items()->whereNull('product_id')->exists()) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Ada item tanpa produk terkait. Konversi ke invoice hanya bisa dilakukan jika semua item terhubung ke produk.');
        }

        $invoice = DB::transaction(function () use ($salesOrder) {
            $invoice = Invoice::create([
                'invoice_number' => app(\App\Services\InvoiceService::class)->generateInvoiceNumber(),
                'customer_id' => $salesOrder->customer_id,
                'vehicle_id' => $salesOrder->vehicle_id,
                'branch_id' => $salesOrder->branch_id,
                'payment_status' => 0,
                'total_amount' => $salesOrder->subtotal,
                'discount' => $salesOrder->discount,
                'discount_type' => 'fixed',
                'discount_percent' => 0,
                'tax_amount' => $salesOrder->tax_amount,
                'grand_total' => $salesOrder->grand_total,
                'paid_amount' => 0,
                'amount_received' => 0,
                'invoice_date' => now()->toDateString(),
                'invoice_type' => 'sales',
                'notes' => $salesOrder->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($salesOrder->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ]);
            }

            $salesOrder->update(['status' => 'converted']);

            return $invoice;
        });

        ActivityLog::record('sales-order.convert', $salesOrder, "Sales order {$salesOrder->order_number} dikonversi ke invoice {$invoice->invoice_number}");

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Sales order berhasil dikonversi menjadi invoice ' . $invoice->invoice_number . '.');
    }

    private function validateData(Request $request): array
    {
        $this->normalizeItems($request);

        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'order_date' => 'required|date',
            'status' => ['required', Rule::in(['draft', 'sent', 'approved', 'rejected', 'converted'])],
            'discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
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
                if (empty($item['discount'])) {
                    $data['items'][$i]['discount'] = 0;
                }
            }
        }

        $request->merge($data);
    }

    private function sumSubtotal(array $items): float
    {
        return (float) collect($items)->sum(function ($item) {
            return (float) $item['quantity'] * (float) $item['unit_price'] - (float) ($item['discount'] ?? 0);
        });
    }

    private function createItems(SalesOrder $salesOrder, array $items): void
    {
        foreach ($items as $item) {
            $salesOrder->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'total_price' => (float) $item['quantity'] * (float) $item['unit_price'] - (float) ($item['discount'] ?? 0),
            ]);
        }
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'SO-' . date('Ymd');
        $last = SalesOrder::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();
        $next = $last ? (int) substr($last->order_number, -4) + 1 : 1;

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
