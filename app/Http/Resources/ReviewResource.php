<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'rating'               => $this->rating,
            'title'                => $this->title,
            'body'                 => $this->body,
            'images'               => $this->images
                ? collect($this->images)->map(fn($img) => asset('storage/' . $img))
                : [],
            'status'               => $this->status,
            'is_verified_purchase' => $this->is_verified_purchase,
            'created_at'           => $this->created_at?->diffForHumans(),
            'user'                 => $this->whenLoaded('user', fn() => [
                'id'         => $this->user->id,
                'name'       => $this->user->name,
                'avatar_url' => $this->user->avatar_url,
            ]),
        ];
    }
}

