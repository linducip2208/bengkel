<?php

namespace App\Http\Resources;

use App\Models\PurchaseHistoryRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseHistoryRecord
 */
class PurchaseHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'action' => $this->action,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
