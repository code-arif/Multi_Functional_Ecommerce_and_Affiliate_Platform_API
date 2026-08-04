<?php

namespace Modules\Catalog\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'logo_url'         => $this->logo_url,
            'website'          => $this->website,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_active'        => $this->is_active,
            'sort_order'       => $this->sort_order,
            'products_count'   => $this->whenCounted('products'),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
