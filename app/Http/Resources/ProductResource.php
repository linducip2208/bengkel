<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'product_type_id' => $this->product_type_id,
            'type' => $this->whenLoaded('productType', fn () => $this->productType->type ?? null),
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit', fn () => $this->unit->name ?? null),
            'supplier_id' => $this->supplier_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'warranty' => $this->warranty,
            'current_stock' => $this->current_stock,
            'minimum_stock' => $this->minimum_stock,
            'rack_location' => $this->rack_location,
            'description' => $this->description,
            'stock_status' => $this->stock_status ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
