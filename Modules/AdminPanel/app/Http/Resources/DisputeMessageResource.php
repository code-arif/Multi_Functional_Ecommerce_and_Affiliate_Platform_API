<?php

namespace Modules\AdminPanel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DisputeMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'user'        => $this->whenLoaded('user', fn() => [
                'uuid' => $this->user?->uuid,
                'name' => $this->user->name,
            ]),
            'message'     => $this->message,
            'attachments' => $this->attachments,
            'created_at'  => $this->created_at,
        ];
    }
}
