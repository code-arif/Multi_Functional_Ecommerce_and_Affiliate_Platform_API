<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'category'              => $this->whenLoaded('category'),
            'brand'                 => $this->whenLoaded('brand'),
            'name'                  => $this->name,
            'slug'                  => $this->slug,
            'sku'                   => $this->sku,
            'type'                  => $this->type,
            'price'                 => (float) $this->price,
            'sale_price'            => (float) $this->sale_price,
            'cost_price'            => (float) $this->cost_price,
            'current_price'         => (float) $this->current_price,
            'is_on_sale'            => $this->is_on_sale,
            'discount_percentage'   => $this->discount_percentage,
            'stock_quantity'        => $this->stock_quantity,
            'stock_status'          => $this->stock_status,
            'is_in_stock'           => $this->is_in_stock,
            'is_low_stock'          => $this->is_low_stock,
            'min_variant_price'     => $this->min_variant_price,
            'max_variant_price'     => $this->max_variant_price,
            'total_variant_stock'   => $this->total_variant_stock,
            'variant_is_in_stock'   => $this->variant_is_in_stock,
            'short_description'     => $this->short_description,
            'description'           => $this->description,
            'thumbnail_url'         => $this->thumbnail_url,
            'images'                => $this->whenLoaded('images'),
            'variants'              => $this->whenLoaded('variants'),
            'attributes'            => $this->whenLoaded('attributes', fn() => $this->attributes->load('values')),
            'reviews'               => $this->whenLoaded('reviews'),
            'average_rating'        => (float) $this->average_rating,
            'total_reviews'         => $this->total_reviews,
            'total_sold'            => $this->total_sold,
            'weight'                => $this->weight,
            'weight_unit'           => $this->weight_unit,
            'tags'                  => $this->tags,
            'is_featured'           => $this->is_featured,
            'is_new'                => $this->is_new,
            'is_bestseller'         => $this->is_bestseller,
            'meta_title'            => $this->meta_title,
            'meta_description'      => $this->meta_description,
            'meta_keywords'         => $this->meta_keywords,
            'status'                => $this->status,
            'published_at'          => $this->published_at,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
