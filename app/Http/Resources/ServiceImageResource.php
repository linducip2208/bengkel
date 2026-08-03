<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'image_url' => $this->image_path ?? null,
            'caption' => $this->caption ?? null,
            'created_at' => $this->created_at,
        ];
    }
}
