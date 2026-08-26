<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'repair_category_id' => $this->repair_category_id,
            'repair_category' => $this->whenLoaded('repairCategory', fn () => $this->repairCategory->repair_category_name ?? null),
            'title' => $this->title,
            'description' => $this->description,
            'service_date' => $this->service_date,
            'charge' => $this->charge,
            'status' => $this->done_status,
            'status_text' => match ((int) $this->done_status) {
                0 => 'Pending',
                1 => 'In Progress',
                2 => 'Done',
                default => 'Unknown',
            },
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'assigned_technicians' => $this->whenLoaded('technicians', fn () => $this->technicians->pluck('name')),
            'jobcard' => new JobcardResource($this->whenLoaded('jobcardDetail')),
            'images' => ServiceImageResource::collection($this->whenLoaded('images')),
            'checkout_results' => CheckoutResultResource::collection($this->whenLoaded('checkoutResults')),
        ];
    }
}
