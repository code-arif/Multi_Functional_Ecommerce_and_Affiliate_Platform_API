<?php

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FaqCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'sort_order'  => $this->sort_order,
            'faqs'        => FaqResource::collection($this->whenLoaded('faqs')),
            'faqs_count'  => $this->whenCounted('faqs'),
            'created_at'  => $this->created_at,
        ];
    }
}
