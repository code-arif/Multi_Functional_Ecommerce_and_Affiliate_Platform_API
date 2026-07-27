<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'shop_name'   => $this->shop_name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'logo_url'    => $this->logo_url,
            'banner_url'  => $this->banner_url,
            'status'      => $this->status,
            'is_active'   => $this->is_active,
            'rating'      => (float) $this->profile?->rating ?? 0,
            'products_count' => $this->whenCounted('products'),
            'created_at'  => $this->created_at,
        ];
    }
}
