<?php

namespace Modules\Checkout\Services;

use Modules\Cart\Models\Cart;
use Modules\Cart\Services\CartService;
use Modules\Auth\Models\User;
use Modules\Auth\Models\Address;
use Modules\Orders\Models\Order;
use Modules\Checkout\Events\CheckoutProcessed;
use Modules\Promotions\Models\Coupon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(
        private CartService $cartService
    ) {}

    /**
     * Get a full order preview (summary) without placing the order.
     */
    public function preview(Cart $cart, User $user, array $data): array
    {
        $this->ensureCartOwnership($cart, $user);

        $itemsByVendor = $this->cartService->getItemsGroupedByVendor($cart);
        $shippingAddress = $this->resolveAddress($data, $user);
        $couponDiscount = $this->validateCoupon($cart->coupon_code, $cart->subtotal);
        $taxRate = $this->getTaxRate($shippingAddress);

        $groups = [];
        $grandTotal = 0;

        foreach ($itemsByVendor as $vendorGroup) {
            $vendorSubtotal = collect($vendorGroup['items'])->sum(fn($item) => $item->total);
            $vendorShipping = $this->calculateShippingCost($vendorSubtotal, $vendorGroup['vendor_id']);
            $vendorTax = round($vendorSubtotal * $taxRate, 2);
            $vendorTotal = $vendorSubtotal + $vendorShipping + $vendorTax;
            $vendorDiscount = $couponDiscount > 0
                ? round($couponDiscount * ($vendorSubtotal / max($cart->subtotal, 1)), 2)
                : 0;

            $groups[] = [
                'vendor_id'      => $vendorGroup['vendor_id'],
                'vendor_name'    => $vendorGroup['vendor_name'],
                'items'          => $vendorGroup['items'],
                'subtotal'       => round($vendorSubtotal, 2),
                'shipping_cost'  => $vendorShipping,
                'tax_amount'     => $vendorTax,
                'discount'       => $vendorDiscount,
                'total'          => round($vendorTotal - $vendorDiscount, 2),
            ];

            $grandTotal += $vendorTotal - $vendorDiscount;
        }

        return [
            'items_by_vendor'  => $groups,
            'subtotal'         => round($cart->subtotal, 2),
            'shipping_total'   => round(collect($groups)->sum('shipping_cost'), 2),
            'tax_total'        => round(collect($groups)->sum('tax_amount'), 2),
            'discount_total'   => round($couponDiscount, 2),
            'grand_total'      => round($grandTotal, 2),
            'coupon_code'      => $cart->coupon_code,
            'shipping_address' => $shippingAddress,
            'tax_rate'         => $taxRate,
            'item_count'       => $cart->item_count,
        ];
    }

    /**
     * Process checkout and create order(s). Supports split-by-vendor.
     */
    public function processCheckout(Cart $cart, User $user, array $data): array
    {
        $this->ensureCartOwnership($cart, $user);

        return DB::transaction(function () use ($cart, $user, $data) {
            $shippingAddress = $this->resolveAddress($data, $user);
            $shippingAddrJson = is_string($shippingAddress) ? $shippingAddress : json_encode($shippingAddress);
            $billingAddrJson = isset($data['billing_address'])
                ? (is_string($data['billing_address']) ? $data['billing_address'] : json_encode($data['billing_address']))
                : $shippingAddrJson;

            $paymentMethod = $data['payment_method'] ?? 'cod';
            $notes = $data['notes'] ?? null;

            $itemsByVendor = $this->cartService->getItemsGroupedByVendor($cart);
            $couponDiscount = $this->validateCoupon($cart->coupon_code, $cart->subtotal);
            $taxRate = $this->getTaxRate($shippingAddress);

            // Increment coupon usage if a valid coupon was applied
            if ($couponDiscount > 0 && $cart->coupon_code) {
                Coupon::where('code', $cart->coupon_code)->increment('used_count');
            }

            $orders = [];

            foreach ($itemsByVendor as $vendorGroup) {
                $vendorSubtotal = collect($vendorGroup['items'])->sum(fn($item) => $item->total);
                $vendorShipping = $this->calculateShippingCost($vendorSubtotal, $vendorGroup['vendor_id']);
                $vendorTax = round($vendorSubtotal * $taxRate, 2);
                $vendorDiscount = $couponDiscount > 0
                    ? round($couponDiscount * ($vendorSubtotal / max($cart->subtotal, 1)), 2)
                    : 0;
                $vendorTotal = round($vendorSubtotal + $vendorShipping + $vendorTax - $vendorDiscount, 2);

                $order = Order::create([
                    'user_id'          => $user->id,
                    'vendor_id'        => $vendorGroup['vendor_id'] > 0 ? $vendorGroup['vendor_id'] : null,
                    'order_number'     => 'ORD-' . strtoupper(Str::random(10)),
                    'subtotal'         => round($vendorSubtotal, 2),
                    'shipping_cost'    => $vendorShipping,
                    'discount_amount'  => $vendorDiscount,
                    'tax_amount'       => $vendorTax,
                    'total'            => $vendorTotal,
                    'coupon_code'      => $cart->coupon_code,
                    'coupon_discount'  => $vendorDiscount,
                    'payment_method'   => $paymentMethod,
                    'payment_status'   => 'pending',
                    'shipping_method'  => $data['shipping_method'] ?? 'standard',
                    'shipping_address' => $shippingAddrJson,
                    'billing_address'  => $billingAddrJson,
                    'notes'            => $notes,
                    'status'           => 'pending',
                    'tracking_token'   => Str::random(32),
                ]);

                foreach ($vendorGroup['items'] as $item) {
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

                    if ($item->variant_id) {
                        $item->variant->decrement('stock_quantity', $item->quantity);
                    } else {
                        $item->product->decrementStock($item->quantity);
                    }
                }

                $order->statusHistories()->create([
                    'from_status'      => null,
                    'to_status'        => 'pending',
                    'changed_by'       => $user->id,
                    'changed_by_name'  => $user->name,
                    'notes'            => 'Order placed.',
                ]);

                $orders[] = $order;
            }

            $cart->items()->delete();

            CheckoutProcessed::dispatch($cart, $orders, $user);

            Log::info('Checkout completed', [
                'user_id' => $user->id,
                'orders'  => collect($orders)->pluck('order_number')->toArray(),
                'total'   => collect($orders)->sum('total'),
            ]);

            return $orders;
        });
    }

    public function getShippingOptions(Cart $cart): array
    {
        $itemsByVendor = $this->cartService->getItemsGroupedByVendor($cart);
        $options = [];

        foreach ($itemsByVendor as $vendorGroup) {
            $vendorSubtotal = collect($vendorGroup['items'])->sum(fn($item) => $item->total);
            $freeOver = config('ecommerce.shipping.free_over', 1000);

            $options[] = [
                'vendor_id'     => $vendorGroup['vendor_id'],
                'vendor_name'   => $vendorGroup['vendor_name'],
                'methods'       => [
                    [
                        'id'     => 'standard',
                        'name'   => 'Standard Shipping',
                        'cost'   => $vendorSubtotal >= $freeOver ? 0 : config('ecommerce.shipping.default_charge', 60),
                        'estimated_days' => '5-7 business days',
                    ],
                    [
                        'id'     => 'express',
                        'name'   => 'Express Shipping',
                        'cost'   => $vendorSubtotal >= $freeOver ? 5 : config('ecommerce.shipping.default_charge', 60) + 30,
                        'estimated_days' => '1-2 business days',
                    ],
                ],
                'free_shipping_over' => $freeOver,
            ];
        }

        return $options;
    }

    public function calculateTax(float $subtotal, array $addressData): float
    {
        $rate = $this->getTaxRate($addressData);
        return round($subtotal * $rate, 2);
    }

    private function ensureCartOwnership(Cart $cart, User $user): void
    {
        if ($cart->user_id && $cart->user_id !== $user->id) {
            abort(403, 'This cart does not belong to you.');
        }
    }

    private function validateCoupon(?string $couponCode, float $subtotal): float
    {
        if (!$couponCode) return 0;

        $coupon = Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->first();

        if (!$coupon) return 0;

        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) return 0;
        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) return 0;

        if ($coupon->type === 'percentage') {
            return min($subtotal * ($coupon->value / 100), $coupon->max_discount ?? PHP_FLOAT_MAX);
        }

        return min($coupon->value, $subtotal);
    }

    private function calculateShippingCost(float $subtotal, int $vendorId): float
    {
        $freeOver = config('ecommerce.shipping.free_over', 1000);
        $baseRate = config('ecommerce.shipping.default_charge', 60);
        return $subtotal >= $freeOver ? 0 : $baseRate;
    }

    private function resolveAddress(array $data, User $user): array
    {
        if (isset($data['address_uuid'])) {
            $address = Address::where('uuid', $data['address_uuid'])
                ->where('user_id', $user->id)->first();
            if ($address) {
                return [
                    'uuid'         => $address->uuid,
                    'label'        => $address->label,
                    'address_line' => $address->address_line ?? '',
                    'city'         => $address->city ?? '',
                    'state'        => $address->state ?? '',
                    'postal_code'  => $address->postal_code ?? '',
                    'country'      => $address->country ?? '',
                ];
            }
        }
        if (isset($data['shipping_address'])) {
            return is_array($data['shipping_address']) ? $data['shipping_address'] : ['full' => $data['shipping_address']];
        }
        $default = $user->defaultAddress;
        if ($default) {
            return [
                'uuid'         => $default->uuid,
                'label'        => $default->label ?? 'Default',
                'address_line' => $default->address_line ?? '',
                'city'         => $default->city ?? '',
                'state'        => $default->state ?? '',
                'postal_code'  => $default->postal_code ?? '',
                'country'      => $default->country ?? '',
            ];
        }
        return ['full' => 'No address provided'];
    }

    private function getTaxRate(array $address): float
    {
        $country = $address['country'] ?? $address['full'] ?? '';
        $rates = [
            'Bangladesh' => 0.05, 'BD' => 0.05,
            'India'      => 0.18,
            'USA'        => 0.0,  'US' => 0.0,
            'UK'         => 0.20, 'GB' => 0.20,
        ];
        return $rates[$country] ?? config('ecommerce.tax.default_rate', 0.0);
    }
}
