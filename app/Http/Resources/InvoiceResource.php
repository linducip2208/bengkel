<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->resource;

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'service_id' => $this->service_id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'customer' => $this->when($this->relationLoaded('service'), function () use ($invoice) {
                return $invoice->service
                    ? new CustomerResource($invoice->service->getRelation('customer'))
                    : null;
            }),
            'invoice_date' => $this->invoice_date,
            'payment_method' => $invoice->paymentMethod?->getAttribute('payment'),
            'payment_status' => $this->payment_status,
            'payment_status_text' => match ((int) $this->payment_status) {
                0 => 'Unpaid',
                1 => 'Partially Paid',
                2 => 'Paid',
                default => 'Unknown',
            },
            'discount' => $this->discount,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
