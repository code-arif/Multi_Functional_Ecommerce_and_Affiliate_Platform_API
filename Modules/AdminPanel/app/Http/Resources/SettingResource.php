<?php

namespace Modules\AdminPanel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'key'         => $this->key,
            'value'       => $this->value,
            'group'       => $this->group,
            'type'        => $this->type,
            'description' => $this->description,
            'is_public'   => $this->is_public,
        ];
    }
}
