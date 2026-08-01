<?php

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'category_id' => $this->category_id,
            'category'    => $this->whenLoaded('category', fn() => [
                'uuid' => $this->category?->uuid,
                'name' => $this->category->name,
            ]),
            'question'    => $this->question,
            'answer'      => $this->answer,
            'sort_order'  => $this->sort_order,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
