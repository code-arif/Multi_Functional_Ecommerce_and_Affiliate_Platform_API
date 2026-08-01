<?php

namespace Modules\AdminPanel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'title'         => $this->title,
            'subtitle'      => $this->subtitle,
            'description'   => $this->description,
            'image_url'     => $this->image ? asset('storage/' . $this->image) : null,
            'mobile_image_url' => $this->mobile_image ? asset('storage/' . $this->mobile_image) : null,
            'link'          => $this->link,
            'position'      => $this->position,
            'sort_order'    => $this->sort_order,
            'is_active'     => $this->is_active,
            'starts_at'     => $this->starts_at,
            'expires_at'    => $this->expires_at,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
