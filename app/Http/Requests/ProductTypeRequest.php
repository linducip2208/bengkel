<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('product_type')?->id;
        return [
            'type' => ['required', 'string', 'max:255', Rule::unique('product_types', 'type')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
