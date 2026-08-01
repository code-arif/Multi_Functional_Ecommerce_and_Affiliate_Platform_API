<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'product_id' => $this->product_id,
            'product'    => $this->whenLoaded('product', fn() => [
                'uuid' => $this->product?->uuid,
                'name'      => $this->product->name,
                'slug'      => $this->product->slug,
                'thumbnail' => $this->product->thumbnail_url,
                'price'     => (float) $this->product->current_price,
                'sale_price' => (float) $this->product->sale_price,
                'rating'    => (float) $this->product->average_rating,
                'in_stock'  => $this->product->is_in_stock,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
