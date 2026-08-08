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
            'discount' => ['numeric', 'min:0'],
            'tax_amount' => ['numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['array', 'required'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
        ];
    }
}
