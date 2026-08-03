<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'checkout_category_id' => $this->checkout_category_id ?? null,
            'result' => $this->result ?? null,
            'notes' => $this->comment ?? null,
            'created_at' => $this->created_at,
        ];
    }
}
