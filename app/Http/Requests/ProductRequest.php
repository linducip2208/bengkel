<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('products', 'code')->ignore($productId)],
            'name' => ['required', 'string', 'max:255'],
            'product_type_id' => ['required', 'exists:product_types,id'],
            'unit_id' => ['required', 'exists:product_units,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Kode Produk',
            'name' => 'Nama Produk',
            'product_type_id' => 'Tipe Produk',
            'unit_id' => 'Satuan',
            'supplier_id' => 'Supplier',
            'price' => 'Harga Jual',
            'cost_price' => 'Harga Beli',
            'warranty' => 'Garansi',
        ];
    }
}
