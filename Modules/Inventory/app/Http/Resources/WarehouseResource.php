<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'vendor_id'     => $this->vendor_id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'full_address'  => $this->full_address,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city'          => $this->city,
            'state'         => $this->state,
            'postal_code'   => $this->postal_code,
            'country'       => $this->country,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'contact_name'  => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'is_active'     => $this->is_active,
            'is_default'    => $this->is_default,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
