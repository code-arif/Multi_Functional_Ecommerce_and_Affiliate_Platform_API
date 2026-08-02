<?php

namespace Modules\Support\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'ticket_number'  => $this->ticket_number,
            'subject'        => $this->subject,
            'description'    => $this->description,
            'category'       => $this->category,
            'priority'       => $this->priority,
            'status'         => $this->status,
            'user'           => $this->whenLoaded('user', fn() => [
                'uuid' => $this->user?->uuid,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'order_uuid'        => optional($this->order)?->uuid,
            'vendor'         => $this->whenLoaded('vendor', fn() => [
                'uuid' => $this->vendor?->uuid,
                'name' => $this->vendor->name,
            ]),
            'assigned_to'    => $this->whenLoaded('assignedTo', fn() => [
                'uuid' => $this->assignedTo?->uuid,
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
