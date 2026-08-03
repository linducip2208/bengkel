<?php

namespace App\Services;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodService extends BaseService
{
    public function index(Request $request)
    {
        $query = PaymentMethod::query();
        if ($request->filled('search')) {
            $query->where('payment', 'like', "%{$request->search}%");
        }
        return $query->orderBy('payment')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment' => 'required|string|max:255|unique:payment_methods',
            'slug' => 'nullable|string|max:255|unique:payment_methods',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        return PaymentMethod::create($validated);
    }

    public function show($id)
    {
        return PaymentMethod::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = PaymentMethod::findOrFail($id);
        $validated = $request->validate([
            'payment' => 'required|string|max:255|unique:payment_methods,payment,' . $id,
            'slug' => 'nullable|string|max:255|unique:payment_methods,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $model->update($validated);
        return $model;
    }

    public function destroy($id)
    {
        $model = PaymentMethod::findOrFail($id);
        $model->delete();
        return $model;
    }
}
