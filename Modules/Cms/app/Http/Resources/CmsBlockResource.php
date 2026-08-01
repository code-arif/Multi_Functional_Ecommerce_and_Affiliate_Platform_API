<?php

namespace Modules\Cms\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CmsBlockResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'type'      => $this->type,
            'content'   => $this->content,
            'data'      => $this->data,
            'is_active' => $this->is_active,
            'order'     => $this->order,
            'created_at' => $this->created_at,
        ];
    }
}
