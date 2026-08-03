<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleBrandRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('vehicle_brand')?->id;
        return [
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'vehicle_brand' => ['required', 'string', 'max:255', Rule::unique('vehicle_brands', 'vehicle_brand')->ignore($id)],
        ];
    }
}
