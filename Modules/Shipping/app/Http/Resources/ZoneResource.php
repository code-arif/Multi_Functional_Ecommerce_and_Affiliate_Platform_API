<?php

namespace Modules\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'countries'    => $this->countries,
            'states'       => $this->states,
            'cities'       => $this->cities,
            'postal_codes' => $this->postal_codes,
            'is_active'    => $this->is_active,
            'rates_count'  => $this->whenCounted('rates'),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
