<?php

namespace Modules\Shipping\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'order_uuid'        => optional($this->order)?->uuid,
            'vendor_uuid'        => optional($this->vendor)?->uuid,
            'courier_uuid'        => optional($this->courier)?->uuid,
            'tracking_number'    => $this->tracking_number,
            'carrier_tracking_code' => $this->carrier_tracking_code,
            'status'             => $this->status,
            'method'             => $this->method,
            'weight'             => $this->weight ? (float) $this->weight : null,
            'shipping_cost'      => (float) $this->shipping_cost,
            'sender_name'        => $this->sender_name,
            'sender_phone'       => $this->sender_phone,
            'recipient_name'     => $this->recipient_name,
            'recipient_phone'    => $this->recipient_phone,
            'notes'              => $this->notes,
            'shipped_at'         => $this->shipped_at,
            'delivered_at'       => $this->delivered_at,
            'courier'            => new CourierResource($this->whenLoaded('courier')),
            'order'              => $this->whenLoaded('order', fn() => [
                'uuid' => $this->order?->uuid,
                'order_number' => $this->order->order_number,
            ]),
            'tracking_histories' => TrackingHistoryResource::collection($this->whenLoaded('trackingHistories')),
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
