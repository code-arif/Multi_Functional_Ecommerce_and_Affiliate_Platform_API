<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'country_id' => $this->country_id,
            'country'    => $this->whenLoaded('country', fn() => [
                'uuid' => $this->country?->uuid,
                'name' => $this->country->name,
                'iso2' => $this->country->iso2,
            ]),
            'name'       => $this->name,
            'code'       => $this->code,
            'is_active'  => $this->is_active,
            'cities_count' => $this->whenCounted('cities'),
            'created_at' => $this->created_at,
        ];
    }
}
