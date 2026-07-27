<?php

namespace Modules\Affiliate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateEarningResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'product'    => $this->whenLoaded('product', fn() => [
                'id'    => $this->product->id,
                'title' => $this->product->title,
                'slug'  => $this->product->slug,
            ]),
            'amount'     => (float) $this->amount,
            'type'       => $this->type,
            'status'     => $this->status,
            'is_available' => $this->is_available,
            'notes'      => $this->notes,
            'available_at' => $this->available_at,
            'paid_at'    => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
