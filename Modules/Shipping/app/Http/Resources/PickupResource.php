<?php

namespace Modules\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PickupResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'vendor_id'      => $this->vendor_id,
            'courier_id'     => $this->courier_id,
            'status'         => $this->status,
            'pickup_date'    => $this->pickup_date,
            'pickup_time_from' => $this->pickup_time_from,
            'pickup_time_to' => $this->pickup_time_to,
            'address'        => $this->address,
            'contact_name'   => $this->contact_name,
            'contact_phone'  => $this->contact_phone,
            'notes'          => $this->notes,
            'parcels'        => $this->parcels,
            'reference_code' => $this->reference_code,
            'scheduled_at'   => $this->scheduled_at,
            'picked_up_at'   => $this->picked_up_at,
            'courier'        => new CourierResource($this->whenLoaded('courier')),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
