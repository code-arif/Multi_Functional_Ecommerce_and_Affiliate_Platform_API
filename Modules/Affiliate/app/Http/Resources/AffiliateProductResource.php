<?php

namespace Modules\Affiliate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'price'            => (float) $this->price,
            'sale_price'       => (float) $this->sale_price,
            'image'            => $this->image ? asset('storage/' . $this->image) : null,
            'affiliate_link'   => $this->affiliate_link,
            'commission_type'  => $this->commission_type,
            'commission_value' => (float) $this->commission_value,
            'is_featured'      => $this->is_featured,
            'is_active'        => $this->is_active,
            'clicks_count'     => $this->whenCounted('clicks', $this->clicks_count),
            'created_at'       => $this->created_at,
        ];
    }
}
