<?php

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'old_status'      => $this->old_status,
            'new_status'      => $this->new_status,
            'actor_type'      => $this->actor_type,
            'changed_by'      => $this->changed_by,
            'changed_by_name' => $this->changed_by_name,
            'notes'           => $this->notes,
            'notify_customer' => (bool) $this->notify_customer,
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}
