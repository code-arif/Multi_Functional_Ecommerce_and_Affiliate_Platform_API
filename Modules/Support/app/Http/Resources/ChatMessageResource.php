<?php

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'user'       => $this->whenLoaded('user', fn() => [
                'uuid' => $this->user?->uuid,
                'name' => $this->user->name,
            ]),
            'message'    => $this->message,
            'type'       => $this->type,
            'is_read'    => $this->is_read,
            'created_at' => $this->created_at,
        ];
    }
}
