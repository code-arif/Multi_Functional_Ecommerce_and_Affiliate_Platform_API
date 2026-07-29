<?php

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'user'           => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'message'        => $this->message,
            'attachments'    => $this->attachments,
            'is_staff_reply' => $this->is_staff_reply,
            'created_at'     => $this->created_at,
        ];
    }
}
