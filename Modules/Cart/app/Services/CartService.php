<?php

namespace Modules\Cart\Services;

use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Catalog\Models\Product;
use Modules\Auth\Models\User;
use Illuminate\Support\Str;

class CartService
{
    public function getCart(?User $user, ?string $sessionId): Cart
    {
        if ($user) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => Str::random(40)]
            );
        } elseif ($sessionId) {
            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId],
                ['session_id' => $sessionId]
            );
        } else {
            $sessionId = Str::random(40);
            $cart = Cart::create(['session_id' => $sessionId]);
        }

        $cart->load('items.product', 'items.variant');
        return $cart;
    }

    public function addItem(Cart $cart, array $data): CartItem
    {
        $product = Product::findOrFail($data['product_id']);
        $unitPrice = $data['unit_price'] ?? ($data['variant_id']
            ? $product->variants()->find($data['variant_id'])?->sale_price ?? $product->current_price
            : $product->current_price);

        $existing = $cart->items()
            ->where('product_id', $data['product_id'])
            ->where('variant_id', $data['variant_id'] ?? null)
            ->first();

        if ($existing) {
            $existing->update([
                'quantity' => $existing->quantity + ($data['quantity'] ?? 1),
            ]);
            return $existing->fresh();
        }

        return $cart->items()->create([
            'product_id'  => $data['product_id'],
            'variant_id'  => $data['variant_id'] ?? null,
            'quantity'    => $data['quantity'] ?? 1,
            'unit_price'  => $unitPrice,
            'total_price' => $unitPrice * ($data['quantity'] ?? 1),
        ]);
    }

    public function updateItem(CartItem $item, array $data): CartItem
    {
        $quantity = $data['quantity'] ?? 1;

        if ($quantity <= 0) {
            $item->delete();
            return $item;
        }

        $item->update([
            'quantity'    => $quantity,
            'total_price' => $item->unit_price * $quantity,
        ]);

        return $item->fresh();
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function applyCoupon(Cart $cart, string $couponCode): Cart
    {
        $cart->update([
            'coupon_code'     => $couponCode,
            'discount_amount' => 0, // Will be calculated properly in Checkout
        ]);

        return $cart->fresh()->load('items.product', 'items.variant');
    }

    public function removeCoupon(Cart $cart): Cart
    {
        $cart->update([
            'coupon_code'     => null,
            'discount_amount' => 0,
        ]);

        return $cart->fresh()->load('items.product', 'items.variant');
    }
}
