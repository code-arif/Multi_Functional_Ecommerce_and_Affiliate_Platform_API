<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecentlyViewedResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'product_id'     => $this->product_id,
            'product'        => $this->whenLoaded('product', fn() => [
                'id'         => $this->product->id,
                'name'       => $this->product->name,
                'slug'       => $this->product->slug,
                'thumbnail'  => $this->product->thumbnail_url,
                'price'      => (float) $this->product->current_price,
                'rating'     => (float) $this->product->average_rating,
            ]),
            'viewed_at'      => $this->created_at,
        ];
    }
}
