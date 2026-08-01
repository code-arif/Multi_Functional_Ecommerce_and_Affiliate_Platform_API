<?php

namespace Modules\Cms\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CmsMenuResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'location'  => $this->location,
            'items'     => $this->items,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
