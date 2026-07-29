<?php

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'user'            => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'order_id'        => $this->order_id,
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
