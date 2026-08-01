<?php

namespace Modules\AdminPanel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'order_uuid'        => optional($this->order)?->uuid,
            'order'            => $this->whenLoaded('order', fn() => [
                'uuid' => $this->order?->uuid,
                'order_number' => $this->order->order_number,
                'total_amount' => $this->order->total_amount,
                'status'       => $this->order->status,
            ]),
            'customer'         => $this->whenLoaded('customer', fn() => [
                'uuid' => $this->customer?->uuid,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ]),
            'vendor'           => $this->whenLoaded('vendor', fn() => [
                'uuid' => $this->vendor?->uuid,
                'name' => $this->vendor->name,
            ]),
            'subject'          => $this->subject,
            'description'      => $this->description,
            'status'           => $this->status,
            'resolution_notes' => $this->resolution_notes,
            'resolved_by'      => $this->whenLoaded('resolvedBy', fn() => [
                'uuid' => $this->resolvedBy?->uuid,
                'name' => $this->resolvedBy->name,
            ]),
            'resolved_at'      => $this->resolved_at,
            'messages'         => DisputeMessageResource::collection($this->whenLoaded('messages')),
            'messages_count'   => $this->whenCounted('messages'),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
