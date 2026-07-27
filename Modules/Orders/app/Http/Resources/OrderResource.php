<?php

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'user'            => $this->whenLoaded('user', fn() => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),
            'items'           => $this->whenLoaded('items'),
            'subtotal'        => (float) $this->subtotal,
            'shipping_cost'   => (float) $this->shipping_cost,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount'      => (float) $this->tax_amount,
            'total'           => (float) $this->total,
            'coupon_code'     => $this->coupon_code,
            'payment_method'  => $this->payment_method,
            'payment_status'  => $this->payment_status,
            'payment'         => $this->whenLoaded('payment'),
            'shipping_method' => $this->shipping_method,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'status'          => $this->status,
            'status_histories' => $this->whenLoaded('statusHistories'),
            'notes'           => $this->notes,
            'admin_note'      => $this->admin_note,
            'tracking_token'  => $this->tracking_token,
            'paid_at'         => $this->paid_at,
            'shipped_at'      => $this->shipped_at,
            'delivered_at'    => $this->delivered_at,
            'cancelled_at'    => $this->cancelled_at,
            'cancel_reason'   => $this->cancel_reason,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
