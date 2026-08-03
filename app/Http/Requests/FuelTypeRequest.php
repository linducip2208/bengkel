<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FuelTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('fuel_type')?->id;
        return [
            'fuel_type' => ['required', 'string', 'max:255', Rule::unique('fuel_types', 'fuel_type')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
