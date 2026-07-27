<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompareListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'item_count'  => $this->item_count,
            'items'       => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'           => $item->id,
                    'product_id'   => $item->product_id,
                    'product'      => $item->relationLoaded('product') ? [
                        'id'          => $item->product->id,
                        'name'        => $item->product->name,
                        'slug'        => $item->product->slug,
                        'sku'         => $item->product->sku,
                        'thumbnail'   => $item->product->thumbnail_url,
                        'price'       => (float) $item->product->current_price,
                        'sale_price'  => (float) $item->product->sale_price,
                        'rating'      => (float) $item->product->average_rating,
                        'reviews'     => $item->product->total_reviews,
                        'brand'       => $item->product->relationLoaded('brand') && $item->product->brand ? [
                            'id'   => $item->product->brand->id,
                            'name' => $item->product->brand->name,
                            'slug' => $item->product->brand->slug,
                        ] : null,
                        'category'    => $item->product->relationLoaded('category') && $item->product->category ? [
                            'id'   => $item->product->category->id,
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
