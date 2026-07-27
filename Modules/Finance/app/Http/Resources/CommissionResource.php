<?php

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'order_id'         => $this->order_id,
            'order_number'     => $this->whenLoaded('order', fn() => $this->order->order_number),
            'vendor_id'        => $this->vendor_id,
            'order_total'      => (float) $this->order_total,
            'commission_rate'  => (float) $this->commission_rate,
            'commission_type'  => $this->commission_type,
            'commission_amount' => (float) $this->commission_amount,
            'status'           => $this->status,
            'is_approved'      => $this->is_approved,
            'approved_at'      => $this->approved_at,
            'created_at'       => $this->created_at,
        ];
    }
}
