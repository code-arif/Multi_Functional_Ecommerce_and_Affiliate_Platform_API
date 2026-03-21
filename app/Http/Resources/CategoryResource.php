<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'image_url'        => $this->image_url,
            'icon'             => $this->icon,
            'is_active'        => $this->is_active,
            'sort_order'       => $this->sort_order,
            'parent_id'        => $this->parent_id,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'products_count'   => $this->whenCounted('products'),
            'parent'   => $this->whenLoaded('parent', fn() => [
                'id'   => $this->parent->id,
                'name' => $this->parent->name,
                'slug' => $this->parent->slug,
            ]),
            'children' => $this->whenLoaded('allChildren',
                fn() => CategoryResource::collection($this->allChildren)
            ),
        ];
    }
}
