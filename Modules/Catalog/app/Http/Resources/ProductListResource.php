<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'category'            => $this->whenLoaded('category', fn() => [
                'uuid' => $this->category?->uuid,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'brand'               => $this->whenLoaded('brand', fn() => [
                'uuid' => $this->brand?->uuid,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ]),
            'name'                => $this->name,
            'slug'                => $this->slug,
            'sku'                 => $this->sku,
            'type'                => $this->type,
            'price'               => (float) $this->price,
            'sale_price'          => (float) $this->sale_price,
            'current_price'       => (float) $this->current_price,
            'is_on_sale'          => $this->is_on_sale,
            'discount_percentage' => $this->discount_percentage,
            'stock_status'        => $this->stock_status,
            'is_in_stock'         => $this->is_in_stock,
            'min_variant_price'   => $this->min_variant_price,
            'max_variant_price'   => $this->max_variant_price,
            'thumbnail_url'       => $this->thumbnail_url,
            'average_rating'      => (float) $this->average_rating,
            'total_reviews'       => $this->total_reviews,
            'total_sold'          => $this->total_sold,
            'is_featured'         => $this->is_featured,
            'is_new'              => $this->is_new,
            'is_bestseller'       => $this->is_bestseller,
            'status'              => $this->status,
            'created_at'          => $this->created_at,
        ];
    }
}
