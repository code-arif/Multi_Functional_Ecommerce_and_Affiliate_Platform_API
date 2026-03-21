<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'subtitle'     => $this->subtitle,
            'image_url'    => $this->image_url,
            'mobile_image' => $this->mobile_image ? asset('storage/'.$this->mobile_image) : null,
            'link'         => $this->link,
            'button_text'  => $this->button_text,
            'position'     => $this->position,
            'sort_order'   => $this->sort_order,
        ];
    }
}
