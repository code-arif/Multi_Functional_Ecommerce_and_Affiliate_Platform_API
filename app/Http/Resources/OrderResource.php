<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'order_number'     => $this->order_number,
            'status'           => $this->status,
            'payment_method'   => $this->payment_method,
            'payment_status'   => $this->payment_status,
            'subtotal'         => (float) $this->subtotal,
            'shipping_charge'  => (float) $this->shipping_charge,
            'discount_amount'  => (float) $this->discount_amount,
            'tax_amount'       => (float) $this->tax_amount,
            'total_amount'     => (float) $this->total_amount,
            'coupon_code'      => $this->coupon_code,
            'customer_note'    => $this->customer_note,
            'tracking_number'  => $this->tracking_number,
            'shipping_carrier' => $this->shipping_carrier,
            'can_be_cancelled' => $this->can_be_cancelled,
            'is_guest_order'   => $this->is_guest_order,
            'shipping_address' => [
                'name'         => $this->shipping_name,
                'phone'        => $this->shipping_phone,
                'email'        => $this->shipping_email,
                'address_line1'=> $this->shipping_address_line1,
                'address_line2'=> $this->shipping_address_line2,
                'city'         => $this->shipping_city,
                'state'        => $this->shipping_state,
                'postal_code'  => $this->shipping_postal_code,
                'country'      => $this->shipping_country,
            ],
            'shipped_at'   => $this->shipped_at?->toDateTimeString(),
            'delivered_at' => $this->delivered_at?->toDateTimeString(),
            'created_at'   => $this->created_at?->toDateTimeString(),

            'items'           => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'                 => $item->id,
                    'product_id'         => $item->product_id,
                    'product_name'       => $item->product_name,
                    'product_sku'        => $item->product_sku,
                    'variant_attributes' => $item->variant_attributes,
                    'product_image'      => $item->product_image
                        ? asset('storage/' . $item->product_image)
                        : null,
                    'unit_price'         => (float) $item->unit_price,
                    'quantity'           => $item->quantity,
                    'subtotal'           => (float) $item->subtotal,
                ])
            ),
            'payment'     => $this->whenLoaded('payment', fn() => $this->payment ? [
                'id'             => $this->payment->id,
                'gateway'        => $this->payment->gateway,
                'transaction_id' => $this->payment->transaction_id,
                'amount'         => (float) $this->payment->amount,
                'status'         => $this->payment->status,
                'paid_at'        => $this->payment->paid_at?->toDateTimeString(),
            ] : null),
            'status_history' => $this->whenLoaded('statusHistories', fn() =>
                $this->statusHistories->map(fn($h) => [
                    'old_status' => $h->old_status,
                    'new_status' => $h->new_status,
                    'comment'    => $h->comment,
                    'created_at' => $h->created_at?->toDateTimeString(),
                ])
            ),
        ];
    }
}
