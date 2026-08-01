<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'product'      => $this->whenLoaded('product', fn() => [
                'uuid' => $this->product?->uuid,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku'  => $this->product->sku,
            ]),
            'variant'      => $this->whenLoaded('variant', fn() => [
                'uuid' => $this->variant?->uuid,
                'name' => $this->variant->name,
                'sku'  => $this->variant->sku,
            ]),
            'warehouse'    => $this->whenLoaded('warehouse', fn() => [
                'uuid' => $this->warehouse?->uuid,
                'name' => $this->warehouse->name,
            ]),
            'type'         => $this->type,
            'delta'        => $this->delta,
            'quantity'     => $this->quantity,
            'stock_before' => $this->stock_before,
            'stock_after'  => $this->stock_after,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'notes'        => $this->notes,
            'created_by'   => $this->whenLoaded('createdBy', fn() => [
                'uuid' => $this->createdBy?->uuid,
                'name' => $this->createdBy->name,
            ]),
            'created_at'   => $this->created_at,
        ];
    }
}
