<?php

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'invoice_number'  => $this->invoice_number,
            'order_number'    => $this->order?->order_number,
            'status'          => $this->status,
            'subtotal'        => (float) $this->subtotal,
            'shipping_charge' => (float) $this->shipping_charge,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount'      => (float) $this->tax_amount,
            'total_amount'    => (float) $this->total_amount,
            'notes'           => $this->notes,
            'issued_at'       => $this->issued_at?->toDateTimeString(),
            'paid_at'         => $this->paid_at?->toDateTimeString(),
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}
