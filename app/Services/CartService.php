<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class CartService
{
    /**
     * Get or create a cart for user or guest session.
     */
    public function getOrCreateCart(?int $userId, ?string $sessionId): Cart
    {
        if ($userId) {
            return Cart::firstOrCreate(
                ['user_id' => $userId],
                ['expires_at' => now()->addDays(30)]
            );
        }

        return Cart::firstOrCreate(
            ['session_id' => $sessionId],
            ['expires_at' => now()->addDays(7)]
        );
    }

    public function getCart(?int $userId, ?string $sessionId): ?Cart
    {
        $query = Cart::with([
            'items.product.images',
            'items.variant',
            'coupon',
        ]);

        if ($userId) {
            return $query->where('user_id', $userId)->first();
        }

        return $query->where('session_id', $sessionId)->first();
    }

    public function addItem(Cart $cart, array $data): CartItem
    {
        $product = Product::findOrFail($data['product_id']);
        $variant = null;

        if (!empty($data['product_variant_id'])) {
            $variant = ProductVariant::findOrFail($data['product_variant_id']);
        }

        // Determine price
        $unitPrice = $variant
            ? ($variant->sale_price ?? $variant->price)
            : ($product->sale_price ?? $product->price);

        // Validate stock
        $stock = $variant ? $variant->stock_quantity : $product->stock_quantity;
        if ($product->manage_stock && $stock < $data['quantity']) {
            throw new \Exception("Insufficient stock. Only {$stock} items available.");
        }

        // Check if item already exists in cart
        $existing = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $product->id)
                            ->where('product_variant_id', $data['product_variant_id'] ?? null)
                            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $data['quantity'];
            if ($product->manage_stock && $stock < $newQty) {
                throw new \Exception("Cannot add more. Only {$stock} items in stock.");
            }
            $existing->update(['quantity' => $newQty, 'unit_price' => $unitPrice]);
            return $existing;
        }

        return CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $product->id,
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'quantity'           => $data['quantity'],
            'unit_price'         => $unitPrice,
        ]);
    }

    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        $product = $item->product;
        $stock   = $item->variant ? $item->variant->stock_quantity : $product->stock_quantity;

        if ($product->manage_stock && $stock < $quantity) {
            throw new \Exception("Only {$stock} items available in stock.");
        }

        $item->update(['quantity' => $quantity]);
        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_id' => null, 'discount_amount' => 0]);
    }

    public function applyCoupon(Cart $cart, string $code, ?int $userId): array
    {
        $couponService = app(CouponService::class);
        $cart->loadMissing('items');

        $subtotal = $cart->subtotal;
        $result   = $couponService->validate($code, $subtotal, $userId);

        $cart->update([
            'coupon_id'       => $result['coupon']->id,
            'discount_amount' => $result['discount'],
        ]);

        return [
            'coupon'          => $result['coupon'],
            'discount_amount' => $result['discount'],
        ];
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->update(['coupon_id' => null, 'discount_amount' => 0]);
    }

    /**
     * Merge guest cart into user cart after login.
     */
    public function mergeGuestCart(string $sessionId, int $userId): void
    {
        $guestCart = Cart::where('session_id', $sessionId)->with('items')->first();
        if (!$guestCart || $guestCart->items->isEmpty()) return;

        $userCart = $this->getOrCreateCart($userId, null);

        foreach ($guestCart->items as $guestItem) {
            try {
                $this->addItem($userCart, [
                    'product_id'         => $guestItem->product_id,
                    'product_variant_id' => $guestItem->product_variant_id,
                    'quantity'           => $guestItem->quantity,
                ]);
            } catch (\Exception $e) {
                // Skip items that can't be added (out of stock, etc.)
            }
        }

        $guestCart->delete();
    }

    public function generateSessionId(): string
    {
        return 'guest_' . Str::random(32);
    }
}
