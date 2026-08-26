<?php

namespace App\Http\Resources;

use App\Models\JobcardDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobcardDetail
 */
class JobcardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'jobcard_no' => $this->jobcard_no,
            'odometer_in' => $this->odometer_in,
            'odometer_out' => $this->odometer_out,
            'in_date' => $this->in_date,
            'out_date' => $this->out_date,
            'next_service_date' => $this->next_service_date,
            'next_service_km' => $this->next_service_km,
            'done_status' => $this->done_status,
            'created_at' => $this->created_at,
        ];
    }
}
