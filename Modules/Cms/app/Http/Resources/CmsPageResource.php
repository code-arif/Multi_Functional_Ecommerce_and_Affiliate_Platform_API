<?php

namespace Modules\Cms\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CmsPageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'slug'            => $this->slug,
            'content'         => $this->content,
            'excerpt'         => $this->excerpt,
            'meta_title'      => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords'   => $this->meta_keywords,
            'og_image'        => $this->og_image_url,
            'template'        => $this->template,
            'status'          => $this->status_label,
            'is_published'    => $this->is_published,
            'is_scheduled'    => $this->is_scheduled,
            'published_at'    => $this->published_at,
            'order'           => $this->order,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
