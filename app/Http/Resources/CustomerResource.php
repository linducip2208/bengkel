<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'vehicles_count' => $this->whenCounted('vehicles'),
            'services_count' => $this->whenCounted('services'),
            'total_spent' => $this->whenLoaded('services', fn () => $this->services->sum('charge')),
            'vehicles' => VehicleResource::collection($this->whenLoaded('vehicles')),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
        ];
    }
}
