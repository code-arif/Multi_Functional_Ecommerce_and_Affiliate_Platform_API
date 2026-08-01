<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'state_id'   => $this->state_id,
            'country_id' => $this->country_id,
            'state'      => $this->whenLoaded('state', fn() => [
                'uuid' => $this->state?->uuid,
                'name' => $this->state->name,
            ]),
            'name'       => $this->name,
            'code'       => $this->code,
            'latitude'   => (float) $this->latitude,
            'longitude'  => (float) $this->longitude,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
