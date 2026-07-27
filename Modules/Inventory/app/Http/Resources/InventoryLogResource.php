<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'product'      => $this->whenLoaded('product', fn() => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku'  => $this->product->sku,
            ]),
            'variant'      => $this->whenLoaded('variant', fn() => [
                'id'   => $this->variant->id,
                'name' => $this->variant->name,
                'sku'  => $this->variant->sku,
            ]),
            'warehouse'    => $this->whenLoaded('warehouse', fn() => [
                'id'   => $this->warehouse->id,
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
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at'   => $this->created_at,
        ];
    }
}
