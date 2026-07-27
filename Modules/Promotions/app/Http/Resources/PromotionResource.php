<?php

namespace Modules\Promotions\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'description'         => $this->description,
            'type'                => $this->type,
            'discount_type'       => $this->discount_type,
            'discount_value'      => (float) $this->discount_value,
            'maximum_discount'    => (float) $this->maximum_discount,
            'discount_label'      => $this->discount_label,
            'min_quantity'        => $this->min_quantity,
            'free_quantity'       => $this->free_quantity,
            'discount_on'         => $this->discount_on,
            'tiers'               => $this->tiers,
            'applies_to'          => $this->applies_to,
            'product_ids'         => $this->product_ids,
            'category_ids'        => $this->category_ids,
            'vendor_ids'          => $this->vendor_ids,
            'usage_limit'         => $this->usage_limit,
            'usage_per_user'      => $this->usage_per_user,
            'used_count'          => $this->used_count,
            'is_active'           => $this->is_active,
            'is_currently_active' => $this->is_currently_active,
            'starts_at'           => $this->starts_at,
            'ends_at'             => $this->ends_at,
            'badge_text'          => $this->badge_text,
            'badge_color'         => $this->badge_color,
            'sort_order'          => $this->sort_order,
            'created_at'          => $this->created_at,
        ];
    }
}
