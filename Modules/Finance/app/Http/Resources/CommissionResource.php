<?php

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'order_uuid'        => optional($this->order)?->uuid,
            'order_number'     => $this->whenLoaded('order', fn() => $this->order->order_number),
            'vendor_uuid'        => optional($this->vendor)?->uuid,
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
