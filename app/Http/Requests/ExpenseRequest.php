<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'expense_date' => ['required', 'date'],
            'label' => ['required', 'string', 'max:255'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'description' => ['nullable', 'string'],
        ];
    }
}
