<?php

namespace App\Http\Resources;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Supplier
 */
class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'tax_id' => $this->tax_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'purchases_count' => $this->whenLoaded('purchases', fn () => $this->purchases->count()),
            'products_count' => $this->whenLoaded('products', fn () => $this->products->count()),
        ];
    }
}
