<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorAddressResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'label'          => $this->label,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city'           => $this->city,
            'state'          => $this->state,
            'postal_code'    => $this->postal_code,
            'country'        => $this->country,
            'latitude'       => $this->latitude,
            'longitude'      => $this->longitude,
            'is_default'     => $this->is_default,
        ];
    }
}
