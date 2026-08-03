<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ColorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('color')?->id;
        return [
            'color' => ['required', 'string', 'max:255', Rule::unique('colors', 'color')->ignore($id)],
            'hex_code' => ['nullable', 'string', 'max:7'],
        ];
    }
}
