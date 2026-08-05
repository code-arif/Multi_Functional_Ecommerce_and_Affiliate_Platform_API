<?php

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'product_id'          => $this->product_id,
            'product_variant_id'  => $this->product_variant_id,
            'vendor_id'           => $this->vendor_id,
            'product_name'        => $this->product_name,
            'product_sku'         => $this->product_sku,
            'variant_attributes'  => $this->variant_attributes,
            'product_image'       => $this->product_image
                ? (str_starts_with($this->product_image, 'http')
                    ? $this->product_image
                    : asset('storage/' . $this->product_image))
                : null,
            'unit_price'          => (float) $this->unit_price,
            'quantity'            => (int) $this->quantity,
            'subtotal'            => (float) $this->subtotal,
        ];
    }
}
