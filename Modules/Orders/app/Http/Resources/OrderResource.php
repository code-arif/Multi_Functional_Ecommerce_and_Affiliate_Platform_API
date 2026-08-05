<?php

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        $user     = $request->user();
        $isAdmin  = (bool) ($user?->isAdmin());
        $isOwner  = $this->user_id && $this->user_id === $user?->id;
        // VendorMiddleware loads the vendor relation; only then do we expose the token to vendors.
        $isVendor = (bool) ($user && $user->relationLoaded('vendor') && $user->vendor);

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'order_number'     => $this->order_number,
            'group_id'         => $this->group_id,
            'user_id'          => $this->user_id,
            'vendor_id'        => $this->vendor_id,
            'status'           => $this->status,
            'status_label'     => $this->status_label,
            'payment_method'   => $this->payment_method,
            'payment_status'   => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,

            // Pricing
            'subtotal'         => (float) $this->subtotal,
            'shipping_charge'  => (float) $this->shipping_charge,
            'discount_amount'  => (float) $this->discount_amount,
            'coupon_discount'  => (float) $this->coupon_discount,
            'tax_amount'       => (float) $this->tax_amount,
            'total_amount'     => (float) $this->total_amount,
            'coupon_code'      => $this->coupon_code,

            // Notes
            'customer_note'    => $this->customer_note,
            'admin_note'       => $this->when($isAdmin, $this->admin_note),

            // Flags
            'can_be_cancelled'       => $this->can_be_cancelled,
            'can_be_customer_cancelled' => $this->can_be_customer_cancelled,
            'is_guest_order'         => $this->is_guest_order,

            // Tracking
            'shipping_method'  => $this->shipping_method,
            'tracking_number'  => $this->tracking_number,
            'shipping_carrier' => $this->shipping_carrier,
            'tracking_token'   => $this->when(
                $this->tracking_token && ($isAdmin || $isOwner || $isVendor),
                $this->tracking_token
            ),

            // Shipping address snapshot
            'shipping_address' => [
                'name'        => $this->shipping_name,
                'phone'       => $this->shipping_phone,
                'email'       => $this->shipping_email,
                'address_line1' => $this->shipping_address_line1,
                'address_line2' => $this->shipping_address_line2,
                'city'        => $this->shipping_city,
                'state'       => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country'     => $this->shipping_country,
            ],

            // Timeline
            'timestamps' => [
                'placed_at'    => $this->created_at?->toDateTimeString(),
                'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
                'processed_at' => $this->processed_at?->toDateTimeString(),
                'shipped_at'   => $this->shipped_at?->toDateTimeString(),
                'delivered_at' => $this->delivered_at?->toDateTimeString(),
                'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
                'refunded_at'  => $this->refunded_at?->toDateTimeString(),
                'paid_at'      => $this->paid_at?->toDateTimeString(),
            ],
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

            // Relations
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id'        => $this->vendor->id,
                'uuid'      => $this->vendor->uuid,
                'shop_name' => $this->vendor->shop_name,
                'status'    => $this->vendor->status,
            ] : null),

            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id'    => $this->user->id,
                'uuid'  => $this->user->uuid,
                'name'  => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null),

            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'id'             => $this->payment->id,
                'uuid'           => $this->payment->uuid,
                'gateway'        => $this->payment->gateway,
                'transaction_id' => $this->payment->transaction_id,
                'amount'         => (float) $this->payment->amount,
                'status'         => $this->payment->status,
                'paid_at'        => $this->payment->paid_at?->toDateTimeString(),
            ] : null),

            'status_histories' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'cancel_requests'  => CancelRequestResource::collection($this->whenLoaded('cancelRequests')),
            'invoice'          => $this->whenLoaded('invoice', fn () => $this->invoice ? new InvoiceResource($this->invoice) : null),
            'group_orders'     => $this->whenLoaded('groupOrders', fn () =>
                OrderResource::collection($this->groupOrders->where('id', '!=', $this->id)->values())
            ),
        ];
    }
}
