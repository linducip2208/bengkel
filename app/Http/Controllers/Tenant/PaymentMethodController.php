<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Income;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMethod::query();
        if ($request->filled('search')) {
            $query->where('payment', 'like', "%{$request->search}%");
        }
        $paymentMethods = $query->orderBy('payment')->paginate(15)->withQueryString();
        return view('payment-methods.index', compact('paymentMethods'));
    }

    public function create()
    {
        return view('payment-methods.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'payment')->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        PaymentMethod::create([
            'payment' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('payment-methods.index')->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('payment-methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'payment')->whereNull('deleted_at')->ignore($paymentMethod->id)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $paymentMethod->update([
            'payment' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('payment-methods.index')->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->invoices()->withoutGlobalScopes()->exists()
            || $paymentMethod->paymentRecords()->exists()
            || Income::withoutGlobalScopes()->where('payment_method_id', $paymentMethod->id)->exists()
        ) {
            return back()->with('error', 'Metode pembayaran tidak bisa dihapus karena masih dipakai oleh invoice, pembayaran, atau pemasukan.');
        }
        $paymentMethod->delete();
        return redirect()->route('payment-methods.index')->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}
