<?php

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'order_id'       => $this->order_id,
            'invoice_number' => $this->invoice_number,
            'subtotal'       => (float) $this->subtotal,
            'shipping_cost'  => (float) $this->shipping_cost,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount'     => (float) $this->tax_amount,
            'total'          => (float) $this->total,
            'paid_amount'    => (float) $this->paid_amount,
            'due_amount'     => (float) $this->due_amount,
            'balance_due'    => $this->balance_due,
            'status'         => $this->status,
            'is_paid'        => $this->is_paid,
            'issued_at'      => $this->issued_at,
            'due_at'         => $this->due_at,
            'paid_at'        => $this->paid_at,
        ];
    }
}
