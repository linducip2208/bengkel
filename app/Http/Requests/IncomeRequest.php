<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'income_date' => ['required', 'date'],
            'label' => ['required', 'string', 'max:255'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
