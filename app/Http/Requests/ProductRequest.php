<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        $route = $this->route('product');
        $productId = is_object($route) ? $route->id : $route;
        $isCreate = !$productId;

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
            'initial_stock' => $isCreate ? ['nullable', 'integer', 'min:0'] : [],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'rack_location' => ['nullable', 'string', 'max:100'],
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
            'initial_stock' => 'Stok Awal',
            'minimum_stock' => 'Stok Minimum',
            'rack_location' => 'Lokasi Rak',
        ];
    }
}
