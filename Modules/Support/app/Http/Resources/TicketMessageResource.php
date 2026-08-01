<?php

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'user'           => $this->whenLoaded('user', fn() => [
                'uuid' => $this->user?->uuid,
                'name' => $this->user->name,
            ]),
            'message'        => $this->message,
            'attachments'    => $this->attachments,
            'is_staff_reply' => $this->is_staff_reply,
            'created_at'     => $this->created_at,
        ];
    }
}
