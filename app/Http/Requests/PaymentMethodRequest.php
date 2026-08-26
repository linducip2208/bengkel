<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $route = $this->route('payment_method');
        $id = is_object($route) ? $route->id : $route;

        return [
            'payment' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'payment')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
