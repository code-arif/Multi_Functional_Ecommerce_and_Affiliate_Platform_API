<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'user'                  => $this->whenLoaded('user', fn() => [
                'id'     => $this->user->id,
                'name'   => $this->user->name,
                'avatar' => $this->user->avatar_url,
            ]),
            'product_id'            => $this->product_id,
            'order_id'              => $this->order_id,
            'rating'                => $this->rating,
            'title'                 => $this->title,
            'body'                  => $this->body,
            'images'                => $this->images,
            'image_urls'            => $this->image_urls,
            'is_verified_purchase'  => $this->is_verified_purchase,
            'status'                => $this->status,
            'admin_response'        => $this->admin_response,
            'vendor_response'       => $this->vendor_response,
            'has_vendor_response'   => $this->has_vendor_response,
            'vendor_responded_at'   => $this->vendor_responded_at,
            'helpful_count'         => (int) $this->helpful_count,
            'is_helpful'            => $this->is_helpful,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
