<?php

namespace Modules\Affiliate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'category_uuid'        => optional($this->category)?->uuid,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'thumbnail'        => $this->thumbnail_url,
            'images'           => $this->images,
            'display_price'    => (float) $this->display_price,
            'affiliate_link'   => $this->affiliate_link,
            'commission_type'  => $this->commission_type,
            'commission_value' => (float) $this->commission_value,
            'commission_label' => $this->commission_label,
            'source_platform'  => $this->source_platform,
            'is_on_commission' => $this->is_on_commission,
            'is_featured'      => $this->is_featured,
            'is_active'        => $this->is_active,
            'clicks_count'     => (int) ($this->clicks_count ?? $this->click_count),
            'conversions_count' => $this->whenCounted('conversions', $this->conversions_count),
            'meta_title'        => $this->meta_title,
            'meta_description'  => $this->meta_description,
            'sort_order'        => $this->sort_order,
            'created_at'        => $this->created_at,
        ];
    }
}
