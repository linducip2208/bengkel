<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierClaim;
use App\Models\WarrantyClaim;
use Illuminate\Http\Request;

class SupplierClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierClaim::with(['supplier', 'product']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $claims = $query->latest()->paginate(20)->withQueryString();

        return view('supplier-claims.index', compact('claims'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $warrantyClaims = WarrantyClaim::orderByDesc('created_at')->limit(200)->get();

        return view('supplier-claims.create', compact('suppliers', 'products', 'warrantyClaims'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'product_id' => 'nullable|exists:products,id',
            'warranty_claim_id' => 'nullable|exists:warranty_claims,id',
            'quantity' => 'nullable|numeric|min:0',
            'claim_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['claim_number'] = $this->generateClaimNumber();
        $validated['status'] = 'pending';

        $claim = SupplierClaim::create($validated);

        return redirect()->route('supplier-claims.show', $claim)
            ->with('success', 'Klaim supplier berhasil dibuat.');
    }

    public function show(SupplierClaim $supplierClaim)
    {
        $supplierClaim->load(['supplier', 'product', 'warrantyClaim']);

        return view('supplier-claims.show', compact('supplierClaim'));
    }

    public function approve(SupplierClaim $supplierClaim)
    {
        $supplierClaim->update(['status' => 'approved']);

        return back()->with('success', 'Klaim supplier disetujui.');
    }

    public function reject(Request $request, SupplierClaim $supplierClaim)
    {
        $validated = $request->validate(['notes' => 'nullable|string|max:5000']);

        $supplierClaim->update([
            'status' => 'rejected',
            'notes' => $validated['notes'] ?? $supplierClaim->notes,
        ]);

        return back()->with('success', 'Klaim supplier ditolak.');
    }

    public function markPaid(SupplierClaim $supplierClaim)
    {
        $supplierClaim->update(['status' => 'paid']);

        return back()->with('success', 'Klaim supplier ditandai dibayar.');
    }

    public function destroy(SupplierClaim $supplierClaim)
    {
        $supplierClaim->delete();

        return redirect()->route('supplier-claims.index')->with('success', 'Klaim supplier dihapus.');
    }

    private function generateClaimNumber(): string
    {
        $prefix = 'SCL-' . now()->format('Ymd') . '-';
        $latest = SupplierClaim::where('claim_number', 'like', $prefix . '%')
            ->orderBy('claim_number', 'desc')
            ->first();

        $lastNumber = $latest ? (int) substr($latest->claim_number, -3) : 0;

        return $prefix . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
    }
}
