<?php

namespace Modules\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'shipping_zone_uuid'        => optional($this->zone)?->uuid,
            'courier_uuid'        => optional($this->courier)?->uuid,
            'name'              => $this->name,
            'method'            => $this->method,
            'base_rate'         => (float) $this->base_rate,
            'rate_per_kg'       => (float) $this->rate_per_kg,
            'rate_per_item'     => (float) $this->rate_per_item,
            'free_shipping_min' => $this->free_shipping_min ? (float) $this->free_shipping_min : null,
            'max_weight'        => $this->max_weight ? (float) $this->max_weight : null,
            'estimated_days_min' => $this->estimated_days_min,
            'estimated_days_max' => $this->estimated_days_max,
            'estimated_delivery' => $this->getEstimatedDeliveryText(),
            'conditions'        => $this->conditions,
            'is_active'         => $this->is_active,
            'zone'              => new ZoneResource($this->whenLoaded('zone')),
            'courier'           => new CourierResource($this->whenLoaded('courier')),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
