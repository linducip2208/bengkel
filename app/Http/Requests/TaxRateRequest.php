<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $route = $this->route('tax_rate');
        $id = is_object($route) ? $route->id : $route;

        return [
            'taxname' => ['required', 'string', 'max:255', Rule::unique('tax_rates', 'taxname')->ignore($id)],
            'tax' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
