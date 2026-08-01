<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediumResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'url'           => $this->url,
            'file_name'     => $this->file_name,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'file_size'     => $this->file_size,
            'width'         => $this->width,
            'height'        => $this->height,
            'extension'     => $this->extension,
            'disk'          => $this->disk,
            'mediable_type' => $this->mediable_type,
            'mediable_id'   => $this->mediable_id,
            'uploaded_by'   => $this->uploaded_by,
            'is_public'     => $this->is_public,
            'created_at'    => $this->created_at,
        ];
    }
}
