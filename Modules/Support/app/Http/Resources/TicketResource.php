<?php

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'ticket_number'  => $this->ticket_number,
            'subject'        => $this->subject,
            'description'    => $this->description,
            'category'       => $this->category,
            'priority'       => $this->priority,
            'status'         => $this->status,
            'user'           => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'order_id'       => $this->order_id,
            'vendor'         => $this->whenLoaded('vendor', fn() => [
                'id'   => $this->vendor->id,
                'name' => $this->vendor->name,
            ]),
            'assigned_to'    => $this->whenLoaded('assignedTo', fn() => [
                'id'   => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ]),
            'messages'       => TicketMessageResource::collection($this->whenLoaded('messages')),
            'messages_count' => $this->whenCounted('messages'),
            'resolved_at'    => $this->resolved_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
