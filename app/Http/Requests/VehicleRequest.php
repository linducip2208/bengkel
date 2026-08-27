<?php

namespace App\Http\Requests;

use App\Support\IdentityNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'number_plate' => IdentityNormalizer::vehiclePlate($this->input('number_plate')),
            'chassis_number' => IdentityNormalizer::serialNumber($this->input('chassis_number')),
            'engine_number' => IdentityNormalizer::serialNumber($this->input('engine_number')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeVehicle = $this->route('vehicle');
        $vehicleId = is_object($routeVehicle) ? $routeVehicle->id : $routeVehicle;

        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_type_id' => ['nullable', 'exists:vehicle_types,id'],
            'vehicle_brand_id' => ['nullable', 'exists:vehicle_brands,id'],
            'other_brand' => ['nullable', 'string', 'max:100'],
            'fuel_type_id' => ['nullable', 'exists:fuel_types,id'],
            'number_plate' => ['nullable', 'string', 'max:20', Rule::unique('vehicles', 'number_plate')->ignore($vehicleId)],
            'chassis_number' => ['nullable', 'string', 'max:50', Rule::unique('vehicles', 'chassis_number')->ignore($vehicleId)],
            'engine_number' => ['nullable', 'string', 'max:50', Rule::unique('vehicles', 'engine_number')->ignore($vehicleId)],
            'model_name' => ['nullable', 'string', 'max:255'],
            'model_year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
