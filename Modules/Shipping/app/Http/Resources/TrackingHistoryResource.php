<?php

namespace Modules\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrackingHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'shipment_id' => $this->shipment_id,
            'status'      => $this->status,
            'location'    => $this->location,
            'description' => $this->description,
            'tracked_at'  => $this->tracked_at,
        ];
    }
}
