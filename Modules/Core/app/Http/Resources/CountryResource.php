<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name'            => $this->name,
            'iso2'            => $this->iso2,
            'iso3'            => $this->iso3,
            'phone_code'      => $this->phone_code,
            'currency_code'   => $this->currency_code,
            'currency_symbol' => $this->currency_symbol,
            'flag_url'        => $this->flag_url,
            'is_active'       => $this->is_active,
            'is_default'      => $this->is_default,
            'states_count'    => $this->whenCounted('states'),
            'created_at'      => $this->created_at,
        ];
    }
}
