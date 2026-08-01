<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecentlyViewedResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'product_uuid'        => optional($this->product)?->uuid,
            'product'        => $this->whenLoaded('product', fn() => [
                'uuid' => $this->product?->uuid,
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
