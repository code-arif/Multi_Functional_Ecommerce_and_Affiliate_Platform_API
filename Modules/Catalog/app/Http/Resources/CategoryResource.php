<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'parent_id'      => $this->parent_id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'icon'           => $this->icon,
            'image_url'      => $this->image_url,
            'banner_url'     => $this->banner_url,
            'meta_title'     => $this->meta_title,
            'meta_description' => $this->meta_description,
            'sort_order'     => $this->sort_order,
            'is_featured'    => $this->is_featured,
            'is_active'      => $this->is_active,
            'products_count' => $this->whenCounted('products', $this->products_count),
            'children'       => $this->whenLoaded('children', fn() =>
                self::collection($this->children)
            ),
            'parent'         => $this->whenLoaded('parent'),
            'created_at'     => $this->created_at,
        ];
    }
}
