<?php

namespace Modules\Affiliate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateConversionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'product'            => $this->whenLoaded('product', fn() => [
                'uuid' => $this->product?->uuid,
                'title' => $this->product->title,
                'slug'  => $this->product->slug,
            ]),
            'user'               => $this->whenLoaded('user', fn() => [
                'uuid' => $this->user?->uuid,
                'name' => $this->user->name,
            ]),
            'order_amount'       => (float) $this->order_amount,
            'commission_amount'  => (float) $this->commission_amount,
            'status'             => $this->status,
            'is_approved'        => $this->is_approved,
            'ip_address'         => $this->ip_address,
            'referrer'           => $this->referrer,
            'converted_at'       => $this->converted_at,
            'created_at'         => $this->created_at,
        ];
    }
}
