<?php

namespace Modules\Checkout\Services;

use Modules\Cart\Models\Cart;
use Modules\Orders\Models\Order;
use Modules\Auth\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function processCheckout(Cart $cart, User $user, array $data): Order
    {
        return DB::transaction(function () use ($cart, $user, $data) {
            $shippingAddress = is_string($data['shipping_address'])
                ? $data['shipping_address']
                : json_encode($data['shipping_address']);

            $billingAddress = isset($data['billing_address'])
                ? (is_string($data['billing_address'])
                    ? $data['billing_address']
                    : json_encode($data['billing_address']))
                : $shippingAddress;

            $order = Order::create([
                'user_id'         => $user->id,
                'order_number'    => 'ORD-' . strtoupper(Str::random(10)),
                'subtotal'        => $cart->subtotal,
                'shipping_cost'   => $data['shipping_cost'] ?? 0,
                'discount_amount' => $cart->discount_amount ?? 0,
                'tax_amount'      => $data['tax_amount'] ?? 0,
                'total'           => $cart->total + ($data['shipping_cost'] ?? 0) + ($data['tax_amount'] ?? 0),
                'coupon_code'     => $cart->coupon_code,
                'coupon_discount' => $cart->discount_amount ?? 0,
                'payment_method'  => $data['payment_method'] ?? 'cod',
                'payment_status'  => 'pending',
                'shipping_method' => $data['shipping_method'] ?? 'standard',
                'shipping_address' => $shippingAddress,
                'billing_address'  => $billingAddress,
                'notes'           => $data['notes'] ?? null,
                'status'          => 'pending',
                'tracking_token'  => Str::random(32),
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id'    => $item->product_id,
                    'variant_id'    => $item->variant_id,
                    'product_name'  => $item->product->name,
                    'product_sku'   => $item->product->sku,
                    'product_image' => $item->product->thumbnail,
                    'quantity'      => $item->quantity,
                    'unit_price'    => $item->unit_price,
                    'total_price'   => $item->total_price,
                ]);

                // Decrement stock
                $item->product->decrementStock($item->quantity);
            }

            $order->statusHistories()->create([
                'from_status'      => null,
                'to_status'        => 'pending',
                'changed_by'       => $user->id,
                'changed_by_name'  => $user->name,
                'notes'            => 'Order placed.',
            ]);

            // Clear the cart
            $cart->items()->delete();

            Log::info('Order placed', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'user_id'      => $user->id,
                'total'        => $order->total,
            ]);

            return $order;
        });
    }

    public function calculateShippingCost(array $items, string $address, string $method = 'standard'): float
    {
        // Basic shipping calculation logic
        $baseRate = config('ecommerce.shipping.default_charge', 60);
        $freeOver = config('ecommerce.shipping.free_over', 1000);

        $subtotal = collect($items)->sum(fn($item) => ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1));

        return $subtotal >= $freeOver ? 0 : $baseRate;
    }
}
