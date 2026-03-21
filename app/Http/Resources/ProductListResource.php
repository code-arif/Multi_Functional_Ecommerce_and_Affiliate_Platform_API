<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'type'                => $this->type,
            'price'               => (float) $this->price,
            'sale_price'          => $this->sale_price ? (float) $this->sale_price : null,
            'current_price'       => (float) $this->current_price,
            'is_on_sale'          => $this->is_on_sale,
            'discount_percentage' => $this->discount_percentage,
            'thumbnail_url'       => $this->thumbnail_url,
            'stock_status'        => $this->stock_status,
            'is_in_stock'         => $this->is_in_stock,
            'average_rating'      => (float) $this->average_rating,
            'total_reviews'       => $this->total_reviews,
            'is_featured'         => $this->is_featured,
            'is_new'              => $this->is_new,
            'category' => $this->whenLoaded('category', fn() => [
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn() => [
                'name' => $this->brand?->name,
                'slug' => $this->brand?->slug,
            ]),
            'primary_image' => $this->whenLoaded('images', fn() => [
                'url' => $this->images->where('is_primary', true)->first()?->image_url
                      ?? $this->images->first()?->image_url,
            ]),
        ];
    }
}
