<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'slug'                 => $this->slug,
            'sku'                  => $this->sku,
            'type'                 => $this->type,
            'price'                => (float) $this->price,
            'sale_price'           => $this->sale_price ? (float) $this->sale_price : null,
            'current_price'        => (float) $this->current_price,
            'is_on_sale'           => $this->is_on_sale,
            'discount_percentage'  => $this->discount_percentage,
            'thumbnail_url'        => $this->thumbnail_url,
            'short_description'    => $this->short_description,
            'description'          => $this->description,
            'stock_status'         => $this->stock_status,
            'stock_quantity'       => $this->stock_quantity,
            'is_in_stock'          => $this->is_in_stock,
            'is_low_stock'         => $this->is_low_stock,
            'average_rating'       => (float) $this->average_rating,
            'total_reviews'        => $this->total_reviews,
            'total_sold'           => $this->total_sold,
            'is_featured'          => $this->is_featured,
            'is_new'               => $this->is_new,
            'is_bestseller'        => $this->is_bestseller,
            'tags'                 => $this->tags ?? [],
            'weight'               => $this->weight,
            'weight_unit'          => $this->weight_unit,
            'meta_title'           => $this->meta_title,
            'meta_description'     => $this->meta_description,
            'meta_keywords'        => $this->meta_keywords,
            'status'               => $this->status,
            'published_at'         => $this->published_at?->toDateTimeString(),
            'created_at'           => $this->created_at?->toDateTimeString(),

            // Relationships
            'category'  => $this->whenLoaded('category', fn() => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'brand'     => $this->whenLoaded('brand', fn() => [
                'id'       => $this->brand->id,
                'name'     => $this->brand->name,
                'slug'     => $this->brand->slug,
                'logo_url' => $this->brand->logo_url,
            ]),
            'images'    => $this->whenLoaded('images', fn() =>
                $this->images->map(fn($img) => [
                    'id'         => $img->id,
                    'url'        => $img->image_url,
                    'alt_text'   => $img->alt_text,
                    'is_primary' => $img->is_primary,
                    'sort_order' => $img->sort_order,
                ])
            ),
            'attributes' => $this->whenLoaded('attributes', fn() =>
                $this->attributes->map(fn($attr) => [
                    'id'     => $attr->id,
                    'name'   => $attr->name,
                    'values' => $attr->values->map(fn($v) => [
                        'id'         => $v->id,
                        'value'      => $v->value,
                        'color_code' => $v->color_code,
                    ]),
                ])
            ),
            'variants'   => $this->whenLoaded('variants', fn() =>
                $this->variants->map(fn($v) => [
                    'id'             => $v->id,
                    'sku'            => $v->sku,
                    'attributes'     => $v->attributes,
                    'price'          => (float) $v->price,
                    'sale_price'     => $v->sale_price ? (float) $v->sale_price : null,
                    'current_price'  => (float) $v->current_price,
                    'stock_quantity' => $v->stock_quantity,
                    'is_in_stock'    => $v->is_in_stock,
                    'image_url'      => $v->image_url,
                ])
            ),
            'reviews'    => $this->whenLoaded('reviews', fn() =>
                ReviewResource::collection($this->reviews)
            ),
        ];
    }
}
