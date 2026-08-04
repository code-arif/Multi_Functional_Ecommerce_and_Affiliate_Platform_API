<?php

namespace Modules\Product\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray($request): array
    {
        $isVariable = $this->type === 'variable';

        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'type'                => $this->type,
            'sku'                 => $this->sku,
            'status'              => $this->status,
            'thumbnail_url'       => $this->thumbnail_url,
            'average_rating'      => (float) $this->average_rating,
            'total_reviews'       => $this->total_reviews,
            'is_featured'         => $this->is_featured,
            'is_new'              => $this->is_new,
            'stock_status'        => $this->stock_status,

            // ─── Pricing ───────────────────────────────────────
            // Variable: price_range = { min, max }, price = null
            // Simple/Affiliate: price_range = null, price = actual price
            'price'               => $isVariable ? null : (float) $this->price,
            'sale_price'          => $isVariable ? null : ($this->sale_price ? (float) $this->sale_price : null),
            'current_price'       => $isVariable ? $this->min_variant_price : (float) $this->current_price,
            'is_on_sale'          => $isVariable ? false : $this->is_on_sale,
            'discount_percentage' => $isVariable ? 0 : $this->discount_percentage,
            'price_range'         => $isVariable ? [
                'min' => $this->min_variant_price,
                'max' => $this->max_variant_price,
            ] : null,

            // ─── Stock ─────────────────────────────────────────
            // Variable: sum of all variant stocks
            // Simple: product table stock
            'stock_quantity'      => $isVariable
                ? $this->total_variant_stock
                : $this->stock_quantity,
            'is_in_stock'         => $isVariable
                ? $this->variant_is_in_stock
                : $this->is_in_stock,

            // ─── Relationships ─────────────────────────────────
            'category'            => $this->whenLoaded('category', fn() => [
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'brand'               => $this->whenLoaded('brand', fn() => [
                'name' => $this->brand?->name,
                'slug' => $this->brand?->slug,
            ]),
            'primary_image'       => $this->whenLoaded('images', fn() => [
                'url' => $this->images->where('is_primary', true)->first()?->image_url
                    ?? $this->images->first()?->image_url,
            ]),
        ];
    }
}
