<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'sale_id' => ['nullable', 'exists:sales,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'invoice_type' => ['required', 'in:service,sales,sales_part'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:fixed,percent'],
            'discount_percent' => ['nullable', 'required_if:discount_type,percent', 'numeric', 'min:0', 'max:100'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'dp_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['array', 'required'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:fixed,percent'],
            'items.*.serial_number' => ['nullable', 'string', 'max:255'],
            'items.*.warranty_expiry' => ['nullable', 'date'],
            'items.*.sold_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->discount_type === 'percent') {
            // The percentage field is authoritative in percent mode.
            $this->merge(['discount' => null]);
        } else {
            // Ignore stale percentage values when nominal mode is active.
            $this->merge(['discount_percent' => null]);
        }

        if ($this->discount === '') {
            $this->merge(['discount' => null]);
        }
        if ($this->tax_amount === '') {
            $this->merge(['tax_amount' => null]);
        }
        if ($this->discount_percent === '') {
            $this->merge(['discount_percent' => null]);
        }
    }

    public function messages(): array
    {
        return [
            'discount.numeric' => 'Diskon nominal harus diisi dengan angka, contoh: 50000.',
            'discount_percent.required_if' => 'Diskon persen wajib diisi dengan angka, contoh: 10.',
            'discount_percent.numeric' => 'Diskon persen harus berupa angka, tanpa tanda %, contoh: 10.',
            'discount_percent.min' => 'Diskon persen tidak boleh kurang dari 0.',
            'discount_percent.max' => 'Diskon persen maksimal 100.',
            'discount_type.in' => 'Tipe diskon harus Rp atau persen.',
        ];
    }
}
