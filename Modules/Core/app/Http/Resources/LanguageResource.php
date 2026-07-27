<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'code'       => $this->code,
            'direction'  => $this->direction,
            'is_default' => $this->is_default,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
