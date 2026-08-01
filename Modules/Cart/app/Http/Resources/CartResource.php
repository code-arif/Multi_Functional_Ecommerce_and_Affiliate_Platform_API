<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'session_id'     => $this->session_id,
            'coupon_code'    => $this->coupon_code,
            'discount_amount' => (float) $this->discount_amount,
            'subtotal'       => (float) $this->subtotal,
            'total'          => (float) $this->total,
            'item_count'     => $this->item_count,
            'is_empty'       => $this->is_empty,
            'items'          => CartItemResource::collection($this->whenLoaded('items')),
            'created_at'     => $this->created_at,
        ];
    }
}
