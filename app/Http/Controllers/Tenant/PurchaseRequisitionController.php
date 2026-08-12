<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    public function index(Request $request)
    {
        $requisitions = PurchaseRequisition::query()
            ->with(['requester', 'items'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('requisition_number', 'like', "%{$request->search}%")
                    ->orWhereHas('requester', fn ($u) => $u->where('name', 'like', "%{$request->search}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchase-requisitions.index', compact('requisitions'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $requisitionNumber = $this->generateNumber();

        return view('purchase-requisitions.create', compact('branches', 'requisitionNumber'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $requisition = DB::transaction(function () use ($validated) {
            $items = $validated['items'];
            unset($validated['items']);

            $requisition = PurchaseRequisition::create(array_merge($validated, [
                'requisition_number' => $this->generateNumber(),
                'requested_by' => auth()->id(),
                'status' => 'draft',
            ]));

            $this->createItems($requisition, $items);

            return $requisition;
        });

        ActivityLog::record('purchase-requisition.create', $requisition, "Permintaan pembelian {$requisition->requisition_number} dibuat");

        return redirect()->route('purchase-requisitions.show', $requisition)
            ->with('success', 'Permintaan pembelian ' . $requisition->requisition_number . ' berhasil dibuat.');
    }

    public function show(PurchaseRequisition $purchaseRequisition)
    {
        $purchaseRequisition->load(['requester', 'approver', 'branch', 'items.product']);

        return view('purchase-requisitions.show', compact('purchaseRequisition'));
    }

    public function destroy(PurchaseRequisition $purchaseRequisition)
    {
        if (!in_array($purchaseRequisition->status, ['draft', 'rejected'])) {
            return redirect()->route('purchase-requisitions.index')
                ->with('error', 'Hanya permintaan dengan status Draft/Ditolak yang dapat dihapus.');
        }

        $purchaseRequisition->delete();

        return redirect()->route('purchase-requisitions.index')
            ->with('success', 'Permintaan pembelian berhasil dihapus.');
    }

    public function submit(PurchaseRequisition $purchaseRequisition)
    {
        if ($purchaseRequisition->status !== 'draft') {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'Hanya permintaan dengan status Draft yang dapat diajukan.');
        }

        $purchaseRequisition->update(['status' => 'submitted']);
        ActivityLog::record('purchase-requisition.submit', $purchaseRequisition, "Permintaan pembelian {$purchaseRequisition->requisition_number} diajukan");

        return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Permintaan pembelian berhasil diajukan.');
    }

    public function approve(PurchaseRequisition $purchaseRequisition)
    {
        if ($purchaseRequisition->status !== 'submitted') {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'Hanya permintaan dengan status Diajukan yang dapat disetujui.');
        }

        $purchaseRequisition->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
        ActivityLog::record('purchase-requisition.approve', $purchaseRequisition, "Permintaan pembelian {$purchaseRequisition->requisition_number} disetujui");

        return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Permintaan pembelian disetujui.');
    }

    public function reject(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        if ($purchaseRequisition->status !== 'submitted') {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'Hanya permintaan dengan status Diajukan yang dapat ditolak.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $purchaseRequisition->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        ActivityLog::record('purchase-requisition.reject', $purchaseRequisition, "Permintaan pembelian {$purchaseRequisition->requisition_number} ditolak");

        return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Permintaan pembelian ditolak.');
    }

    public function convertToPurchaseOrder(PurchaseRequisition $purchaseRequisition)
    {
        if ($purchaseRequisition->status === 'converted') {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'Permintaan pembelian sudah dikonversi menjadi purchase order.');
        }

        if ($purchaseRequisition->status !== 'approved') {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'Hanya permintaan dengan status Disetujui yang dapat dikonversi.');
        }

        $purchaseRequisition->load('items.product');

        $supplierId = $purchaseRequisition->items
            ->pluck('product.supplier_id')
            ->filter()
            ->first();

        if (!$supplierId) {
            return redirect()->route('purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'Tidak dapat menentukan supplier. Pastikan produk pada item terkait memiliki supplier.');
        }

        $purchaseOrder = DB::transaction(function () use ($purchaseRequisition, $supplierId) {
            $subtotal = (float) $purchaseRequisition->items->sum(function ($item) {
                return (float) $item->quantity * (float) ($item->product->cost_price ?? 0);
            });

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'supplier_id' => $supplierId,
                'branch_id' => $purchaseRequisition->branch_id,
                'order_date' => now()->toDateString(),
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'grand_total' => $subtotal,
                'notes' => 'Dikonversi dari permintaan pembelian #' . $purchaseRequisition->requisition_number,
                'created_by' => auth()->id(),
            ]);

            foreach ($purchaseRequisition->items as $item) {
                $unitPrice = (float) ($item->product->cost_price ?? 0);

                $purchaseOrder->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->product?->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => (float) $item->quantity * $unitPrice,
                ]);
            }

            $purchaseRequisition->update(['status' => 'converted']);

            return $purchaseOrder;
        });

        ActivityLog::record('purchase-requisition.convert', $purchaseRequisition, "Permintaan pembelian {$purchaseRequisition->requisition_number} dikonversi ke PO {$purchaseOrder->po_number}");

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Permintaan pembelian berhasil dikonversi menjadi purchase order ' . $purchaseOrder->po_number . '.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string|max:255',
        ]);
    }

    private function createItems(PurchaseRequisition $purchaseRequisition, array $items): void
    {
        foreach ($items as $item) {
            $purchaseRequisition->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    private function generateNumber(): string
    {
        $prefix = 'REQ-' . date('Ymd');
        $last = PurchaseRequisition::where('requisition_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();
        $next = $last ? (int) substr($last->requisition_number, -4) + 1 : 1;

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
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
