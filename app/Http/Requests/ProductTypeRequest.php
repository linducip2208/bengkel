<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $route = $this->route('product_type');
        $id = is_object($route) ? $route->id : $route;

        return [
            'type' => ['required', 'string', 'max:255', Rule::unique('product_types', 'type')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
