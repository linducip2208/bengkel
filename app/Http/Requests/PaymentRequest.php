<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }
}
