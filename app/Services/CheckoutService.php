<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private CouponService $couponService,
        private OrderService  $orderService
    ) {}

    public function process(array $data, ?int $userId, ?string $sessionId): Order
    {
        return DB::transaction(function () use ($data, $userId, $sessionId) {
            // Get cart
            $cart = Cart::with(['items.product', 'items.variant', 'coupon'])
                        ->when($userId, fn($q) => $q->where('user_id', $userId))
                        ->when(!$userId, fn($q) => $q->where('session_id', $sessionId))
                        ->first();

            if (!$cart || $cart->items->isEmpty()) {
                throw new \Exception('Your cart is empty.');
            }

            // Validate stock for all items
            foreach ($cart->items as $item) {
                $stock = $item->variant
                    ? $item->variant->stock_quantity
                    : $item->product->stock_quantity;

                if ($item->product->manage_stock && $stock < $item->quantity) {
                    throw new \Exception(
                        "'{$item->product->name}' has insufficient stock."
                    );
                }
            }

            // Calculate totals
            $subtotal       = $cart->subtotal;
            $shippingCharge = $this->calculateShipping($subtotal, $data['shipping_city'] ?? '');
            $discountAmount = $cart->discount_amount ?? 0;
            $totalAmount    = max(0, $subtotal + $shippingCharge - $discountAmount);

            // Create order
            $order = Order::create([
                'order_number'          => Order::generateOrderNumber(),
                'user_id'               => $userId,
                'status'                => 'pending',
                'subtotal'              => $subtotal,
                'shipping_charge'       => $shippingCharge,
                'discount_amount'       => $discountAmount,
                'tax_amount'            => 0,
                'total_amount'          => $totalAmount,
                'coupon_id'             => $cart->coupon_id,
                'coupon_code'           => $cart->coupon?->code,
                'shipping_name'         => $data['shipping_name'],
                'shipping_phone'        => $data['shipping_phone'],
                'shipping_email'        => $data['shipping_email'] ?? null,
                'shipping_address_line1'=> $data['shipping_address_line1'],
                'shipping_address_line2'=> $data['shipping_address_line2'] ?? null,
                'shipping_city'         => $data['shipping_city'],
                'shipping_state'        => $data['shipping_state'] ?? null,
                'shipping_postal_code'  => $data['shipping_postal_code'] ?? null,
                'shipping_country'      => $data['shipping_country'] ?? 'Bangladesh',
                'payment_method'        => $data['payment_method'] ?? 'cod',
                'payment_status'        => 'pending',
                'customer_note'         => $data['customer_note'] ?? null,
                'guest_email'           => !$userId ? ($data['shipping_email'] ?? null) : null,
                'guest_token'           => !$userId ? Str::random(32) : null,
            ]);

            // Create order items & decrement stock
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'           => $order->id,
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name'       => $item->product->name,
                    'product_sku'        => $item->variant?->sku ?? $item->product->sku,
                    'variant_attributes' => $item->variant?->attributes,
                    'product_image'      => $item->product->thumbnail,
                    'unit_price'         => $item->unit_price,
                    'quantity'           => $item->quantity,
                    'subtotal'           => $item->unit_price * $item->quantity,
                ]);

                // Decrement product stock
                if ($item->product->manage_stock) {
                    if ($item->variant) {
                        $item->variant->decrement('stock_quantity', $item->quantity);
                    }
                    $item->product->decrementStock($item->quantity);
                }
            }

            // Create initial payment record
            Payment::create([
                'order_id' => $order->id,
                'gateway'  => $order->payment_method,
                'amount'   => $order->total_amount,
                'currency' => 'BDT',
                'status'   => $order->payment_method === 'cod' ? 'pending' : 'pending',
            ]);

            // Record coupon usage
            if ($cart->coupon_id && $userId) {
                $this->couponService->recordUsage(
                    $cart->coupon,
                    $userId,
                    $order->id,
                    $discountAmount
                );
            }

            // Initial status history
            OrderStatusHistory::create([
                'order_id'        => $order->id,
                'old_status'      => '',
                'new_status'      => 'pending',
                'comment'         => 'Order placed successfully.',
                'notify_customer' => true,
            ]);

            // Clear cart
            $cart->items()->delete();
            $cart->update(['coupon_id' => null, 'discount_amount' => 0]);

            // Fire event (sends email, notifications)
            event(new OrderPlaced($order));

            Log::channel('orders')->info('Order placed', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'total'        => $order->total_amount,
            ]);

            return $order->load(['items', 'payment']);
        });
    }

    private function calculateShipping(float $subtotal, string $city): float
    {
        $freeShippingOver = (float) \App\Models\Setting::get('free_shipping_over', 1000);
        $shippingCharge   = (float) \App\Models\Setting::get('shipping_charge', 60);

        return $subtotal >= $freeShippingOver ? 0 : $shippingCharge;
    }
}
