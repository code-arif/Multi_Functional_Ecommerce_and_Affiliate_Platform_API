<?php

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'user'            => $this->whenLoaded('user', fn() => [
                'uuid' => $this->user?->uuid,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'order_uuid'        => optional($this->order)?->uuid,
            'subject'         => $this->subject,
            'status'          => $this->status,
            'assigned_to'     => $this->assigned_to,
            'last_message_at' => $this->last_message_at,
            'messages'        => ChatMessageResource::collection($this->whenLoaded('messages')),
            'messages_count'  => $this->whenCounted('messages'),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
