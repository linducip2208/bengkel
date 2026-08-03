<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'sale_date' => ['required', 'date'],
            'price' => ['required', 'numeric', 'min:1'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,completed,cancelled'],
            'description' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Form uses `price` and `description`; mirror to `total_amount` and `notes`
        // so downstream code reading those columns still works.
        $this->merge([
            'total_amount' => $this->input('price'),
            'notes' => $this->input('description') ?? $this->input('notes'),
        ]);
    }
}
