<?php

namespace Modules\Catalog\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'image_url'        => $this->image_url,
            'icon'             => $this->icon,
            'is_active'        => $this->is_active,
            'is_featured'      => $this->is_featured,
            'status'           => $this->status,
            'commission_rate'  => $this->commission_rate,
            'depth'            => $this->depth,
            'sort_order'       => $this->sort_order,
            'parent_id'        => $this->parent_id,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_by'       => $this->created_by,
            'updated_by'       => $this->updated_by,
            'products_count'   => $this->whenCounted('products'),
            'parent'   => $this->whenLoaded('parent', fn() => [
                'id'   => $this->parent->id,
                'name' => $this->parent->name,
                'slug' => $this->parent->slug,
            ]),
            'creator'  => $this->whenLoaded('creator', fn() => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'updater'  => $this->whenLoaded('updater', fn() => [
                'id'   => $this->updater->id,
                'name' => $this->updater->name,
            ]),
            'products' => $this->whenLoaded('products', fn() => $this->products->map(fn($p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'name' => $p->name,
                'slug' => $p->slug,
                'status' => $p->status,
                'thumbnail_url' => $p->thumbnail_url,
                'price' => $p->price,
                'sale_price' => $p->sale_price,
            ])),
            'children' => $this->whenLoaded('allChildren',
                fn() => CategoryResource::collection($this->allChildren)
            ),
        ];
    }
}
