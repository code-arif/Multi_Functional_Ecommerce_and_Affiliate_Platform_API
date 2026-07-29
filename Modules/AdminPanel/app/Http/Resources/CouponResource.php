<?php

namespace Modules\AdminPanel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'code'                => $this->code,
            'type'                => $this->type,
            'value'               => (float) $this->value,
            'minimum_order_amount' => $this->minimum_order_amount ? (float) $this->minimum_order_amount : null,
            'maximum_discount'    => $this->maximum_discount ? (float) $this->maximum_discount : null,
            'usage_limit'         => $this->usage_limit,
            'usage_per_user'      => $this->usage_per_user,
            'used_count'          => $this->used_count,
            'usages_count'        => $this->whenCounted('usages'),
            'is_active'           => $this->is_active,
            'starts_at'           => $this->starts_at,
            'expires_at'          => $this->expires_at,
            'description'         => $this->description,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
