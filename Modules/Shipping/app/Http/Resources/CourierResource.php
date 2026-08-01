<?php

namespace Modules\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name'               => $this->name,
            'slug'               => $this->slug,
            'display_name'       => $this->display_name,
            'description'        => $this->description,
            'website'            => $this->website,
            'tracking_url_template' => $this->tracking_url_template,
            'contact_phone'      => $this->contact_phone,
            'contact_email'      => $this->contact_email,
            'supported_services' => $this->supported_services,
            'is_active'          => $this->is_active,
            'sort_order'         => $this->sort_order,
            'rates_count'        => $this->whenCounted('rates'),
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
