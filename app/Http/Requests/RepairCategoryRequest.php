<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepairCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('repair_category')?->id;
        return [
            'repair_category_name' => ['required', 'string', 'max:255', Rule::unique('repair_categories', 'repair_category_name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
