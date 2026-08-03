<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceObservationPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'observation_point_id' => $this->observation_point_id ?? null,
            'result' => $this->checked ?? null,
            'notes' => $this->comment ?? null,
            'created_at' => $this->created_at,
        ];
    }
}
