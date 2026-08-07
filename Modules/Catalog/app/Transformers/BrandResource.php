<?php

namespace Modules\Catalog\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid'             => $this->uuid,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'logo_url'         => $this->logo_url,
            'banner_url'       => $this->banner_url,
            'website'          => $this->website,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_active'        => $this->is_active,
            'is_featured'      => $this->is_featured,
            'status'           => $this->status,
            'sort_order'       => $this->sort_order,
            'created_by'       => $this->created_by,
            'updated_by'       => $this->updated_by,
            'products_count'   => $this->whenCounted('products'),
            'creator'  => $this->whenLoaded('creator', fn() => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'updater'  => $this->whenLoaded('updater', fn() => [
                'id'   => $this->updater->id,
                'name' => $this->updater->name,
            ]),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
