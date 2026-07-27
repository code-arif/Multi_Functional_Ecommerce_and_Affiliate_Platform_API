<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'product'    => $this->whenLoaded('product', fn() => [
                'id'       => $this->product->id,
                'name'     => $this->product->name,
                'slug'     => $this->product->slug,
                'sku'      => $this->product->sku,
                'image'    => $this->product->thumbnail_url,
                'price'    => (float) $this->product->current_price,
                'is_in_stock' => $this->product->is_in_stock,
            ]),
            'variant'    => $this->whenLoaded('variant'),
            'quantity'   => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
        ];
    }
}
