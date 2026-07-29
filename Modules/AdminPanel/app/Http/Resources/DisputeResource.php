<?php

namespace Modules\AdminPanel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'order_id'         => $this->order_id,
            'order'            => $this->whenLoaded('order', fn() => [
                'id'           => $this->order->id,
                'order_number' => $this->order->order_number,
                'total_amount' => $this->order->total_amount,
                'status'       => $this->order->status,
            ]),
            'customer'         => $this->whenLoaded('customer', fn() => [
                'id'   => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ]),
            'vendor'           => $this->whenLoaded('vendor', fn() => [
                'id'   => $this->vendor->id,
                'name' => $this->vendor->name,
            ]),
            'subject'          => $this->subject,
            'description'      => $this->description,
            'status'           => $this->status,
            'resolution_notes' => $this->resolution_notes,
            'resolved_by'      => $this->whenLoaded('resolvedBy', fn() => [
                'id'   => $this->resolvedBy->id,
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
