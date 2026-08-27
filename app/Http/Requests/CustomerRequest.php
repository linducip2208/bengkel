<?php

namespace App\Http\Requests;

use App\Support\IdentityNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => IdentityNormalizer::email($this->input('email')),
            'phone' => IdentityNormalizer::indonesianPhone($this->input('phone')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $route = $this->route('customer');
        $customerId = is_object($route) ? $route->id : $route;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers')->ignore($customerId)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('customers')->ignore($customerId)],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
