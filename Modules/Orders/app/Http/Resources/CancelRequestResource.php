<?php

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CancelRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'order_id'       => $this->order_id,
            'order_number'   => $this->whenLoaded('order', fn () => $this->order?->order_number),
            'reason'         => $this->reason,
            'status'         => $this->status,
            'admin_response' => $this->admin_response,
            'reviewed_by'    => $this->reviewed_by,
            'reviewed_at'    => $this->reviewed_at?->toDateTimeString(),
            'user'           => $this->whenLoaded('user', fn () => $this->user ? [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
