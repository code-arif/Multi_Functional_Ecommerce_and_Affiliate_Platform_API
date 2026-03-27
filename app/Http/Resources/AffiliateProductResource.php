<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class AffiliateProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'thumbnail_url'   => $this->thumbnail_url,
            'images'          => $this->images
                ? collect($this->images)->map(fn($img) => asset('storage/' . $img))
                : [],
            'display_price'   => $this->display_price ? (float) $this->display_price : null,
            'affiliate_link'  => $this->affiliate_link,
            'source_platform' => $this->source_platform,
            'click_count'     => $this->click_count,
            'meta_title'      => $this->meta_title,
            'is_active'        => $this->is_active,
            'meta_description'=> $this->meta_description,
            'category'        => $this->whenLoaded('category', fn() => [
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
        ];
    }
}
