<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::query();
        if ($request->filled('search')) {
            $query->where(fn($q) => $q->where('code', 'like', "%{$request->search}%")->orWhere('name', 'like', "%{$request->search}%"));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        $vouchers = $query->latest()->paginate(20)->withQueryString();
        return view('vouchers.index', compact('vouchers'));
    }

    public function create() { return view('vouchers.create'); }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        Voucher::create($validated);
        return redirect()->route('vouchers.index')->with('success', 'Voucher dibuat.');
    }

    public function edit(Voucher $voucher) { return view('vouchers.edit', compact('voucher')); }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $this->validateData($request, $voucher->id);
        $voucher->update($validated);
        return redirect()->route('vouchers.index')->with('success', 'Voucher diperbarui.');
    }

    public function destroy(Voucher $voucher)
    {
        if ($voucher->used_count > 0) return back()->with('error', 'Voucher tidak bisa dihapus karena sudah digunakan.');
        $voucher->delete();
        return redirect()->route('vouchers.index')->with('success', 'Voucher dihapus.');
    }

    /** AJAX validate voucher code at POS/invoice time */
    public function validateCode(Request $request): JsonResponse
    {
        $code = trim((string) $request->code);
        $subtotal = (float) $request->subtotal;
        $voucher = Voucher::where('code', $code)->first();
        if (!$voucher) return response()->json(['ok' => false, 'error' => 'Kode tidak ditemukan']);
        if (!$voucher->isUsable()) return response()->json(['ok' => false, 'error' => 'Voucher tidak aktif/sudah habis/expired']);
        if ($subtotal < $voucher->min_purchase) return response()->json(['ok' => false, 'error' => 'Minimum pembelian Rp ' . number_format($voucher->min_purchase, 0, ',', '.')]);
        return response()->json([
            'ok' => true,
            'voucher_id' => $voucher->id,
            'discount' => $voucher->calculateDiscount($subtotal),
            'name' => $voucher->name,
        ]);
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('vouchers', 'code')->ignore($id)],
            'name' => 'required|string|max:255',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
