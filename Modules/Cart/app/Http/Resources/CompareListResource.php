<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompareListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'item_count'  => $this->item_count,
            'items'       => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'uuid'         => $item->uuid,
                    'product_uuid'  => optional($item->product)?->uuid,
                    'product'      => $item->relationLoaded('product') ? [
                        'uuid'        => $item->product?->uuid,
                        'name'        => $item->product->name,
                        'slug'        => $item->product->slug,
                        'sku'         => $item->product->sku,
                        'thumbnail'   => $item->product->thumbnail_url,
                        'price'       => (float) $item->product->current_price,
                        'sale_price'  => (float) $item->product->sale_price,
                        'rating'      => (float) $item->product->average_rating,
                        'reviews'     => $item->product->total_reviews,
                        'brand'       => $item->product->relationLoaded('brand') && $item->product->brand ? [
                            'uuid' => $item->product->brand?->uuid,
                            'name' => $item->product->brand->name,
                            'slug' => $item->product->brand->slug,
                        ] : null,
                        'category'    => $item->product->relationLoaded('category') && $item->product->category ? [
                            'uuid' => $item->product->category?->uuid,
                            'name' => $item->product->category->name,
                            'slug' => $item->product->category->slug,
                        ] : null,
                    ] : null,
                    'added_at'     => $item->created_at,
                ])
            ),
            'created_at'  => $this->created_at,
        ];
    }
}
