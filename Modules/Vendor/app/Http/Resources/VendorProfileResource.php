<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'business_type'                 => $this->business_type,
            'business_registration_number'  => $this->business_registration_number,
            'tax_id'                        => $this->tax_id,
            'website'                       => $this->website,
            'social_facebook'               => $this->social_facebook,
            'social_instagram'              => $this->social_instagram,
            'social_youtube'                => $this->social_youtube,
            'return_policy'                 => $this->return_policy,
            'shipping_policy'               => $this->shipping_policy,
            'is_featured'                   => $this->is_featured,
            'sort_order'                    => $this->sort_order,
        ];
    }
}
