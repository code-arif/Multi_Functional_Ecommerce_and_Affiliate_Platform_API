<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id'              => $this->id,
            'total_items'     => $this->total_items,
            'subtotal'        => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'total'           => (float) $this->total,
            'coupon'          => $this->whenLoaded('coupon', fn() => $this->coupon ? [
                'code'  => $this->coupon->code,
                'type'  => $this->coupon->type,
                'value' => (float) $this->coupon->value,
            ] : null),
            'items' => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'         => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'product'    => $item->product ? [
                        'id'            => $item->product->id,
                        'name'          => $item->product->name,
                        'slug'          => $item->product->slug,
                        'thumbnail_url' => $item->product->thumbnail_url,
                        'stock_status'  => $item->product->stock_status,
                    ] : null,
                    'variant'    => $item->variant ? [
                        'id'         => $item->variant->id,
                        'attributes' => $item->variant->attributes,
                        'sku'        => $item->variant->sku,
                    ] : null,
                ])
            ),
        ];
    }
}
