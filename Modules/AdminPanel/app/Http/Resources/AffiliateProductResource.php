<?php

namespace Modules\AdminPanel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'display_price'    => $this->display_price ? (float) $this->display_price : null,
            'thumbnail_url'    => $this->thumbnail_url,
            'affiliate_link'   => $this->affiliate_link,
            'commission_type'  => $this->commission_type,
            'commission_value' => (float) $this->commission_value,
            'clicks_count'     => $this->whenCounted('clicks'),
            'is_featured'      => $this->is_featured,
            'is_active'        => $this->is_active,
            'sort_order'       => $this->sort_order,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
