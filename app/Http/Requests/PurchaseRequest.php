<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'status' => ['nullable', 'in:draft,ordered,received,cancelled'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'Supplier',
            'purchase_date' => 'Tanggal Pembelian',
            'items' => 'Item Pembelian',
            'items.*.product_id' => 'Produk',
            'items.*.quantity' => 'Jumlah',
            'items.*.unit_price' => 'Harga Satuan',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal satu item produk harus ditambahkan.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
        ];
    }
}
