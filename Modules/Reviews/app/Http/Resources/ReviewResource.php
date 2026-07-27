<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'user'                => $this->whenLoaded('user', fn() => [
                'id'     => $this->user->id,
                'name'   => $this->user->name,
                'avatar' => $this->user->avatar_url,
            ]),
            'product_id'          => $this->product_id,
            'rating'              => $this->rating,
            'title'               => $this->title,
            'body'                => $this->body,
            'images'              => $this->images,
            'is_verified_purchase' => $this->is_verified_purchase,
            'status'              => $this->status,
            'admin_response'      => $this->admin_response,
            'created_at'          => $this->created_at,
        ];
    }
}
