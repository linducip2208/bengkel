<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUnitRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('product_unit')?->id;
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('product_units')->ignore($id)],
            'abbreviation' => ['nullable', 'string', 'max:10'],
        ];
    }
}
