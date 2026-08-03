<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'vehicle_type_id' => $this->vehicle_type_id,
            'vehicle_type' => $this->whenLoaded('vehicleType', fn() => $this->vehicleType->vehicle_type),
            'vehicle_brand_id' => $this->vehicle_brand_id,
            'vehicle_brand' => $this->whenLoaded('vehicleBrand', fn() => $this->vehicleBrand->vehicle_brand),
            'fuel_type_id' => $this->fuel_type_id,
            'fuel_type' => $this->whenLoaded('fuelType', fn() => $this->fuelType->fuel_type),
            'plate_number' => $this->number_plate,
            'chassis_number' => $this->chassis_number,
            'engine_number' => $this->engine_number,
            'model' => $this->model_name,
            'year' => $this->model_year,
            'color' => $this->color,
            'odometer' => $this->odometer,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'services_count' => $this->whenCounted('services'),
        ];
    }
}
