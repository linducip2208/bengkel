<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('vehicle_type')?->id;
        return [
            'vehicle_type' => ['required', 'string', 'max:255', Rule::unique('vehicle_types', 'vehicle_type')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
