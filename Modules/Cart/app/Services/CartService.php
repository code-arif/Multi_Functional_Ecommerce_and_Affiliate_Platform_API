<?php

namespace Modules\Cart\Services;

use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Cart\Events\CartUpdated;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
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

    /**
     * Add an item to the cart with stock validation (supports simple + variable products).
     */
    public function addItem(Cart $cart, array $data): CartItem
    {
        $product = Product::findOrFail($data['product_id']);
        $requestedQty = $data['quantity'] ?? 1;
        $maxQty = config('ecommerce.cart.max_quantity', 100);

        if ($requestedQty > $maxQty) {
            abort(400, "Maximum quantity per item is {$maxQty}.");
        }

        // Stock validation: variable products check variant stock, simple products check product stock
        if ($data['variant_id'] ?? null) {
            $variant = ProductVariant::findOrFail($data['variant_id']);
            if ($variant->stock_quantity < $requestedQty) {
                abort(400, "Only {$variant->stock_quantity} units available for this variant.");
            }
        } elseif ($product->manage_stock) {
            $existing = $cart->items()
                ->where('product_id', $data['product_id'])
                ->whereNull('variant_id')
                ->first();

            $currentQty = $existing ? $existing->quantity : 0;
            if (($currentQty + $requestedQty) > $product->stock_quantity) {
                abort(400, "Only {$product->stock_quantity} units available.");
            }
        }

        $unitPrice = $data['unit_price'] ?? ($data['variant_id'] ?? false
            ? ($product->variants()->find($data['variant_id'])?->sale_price ?? $product->current_price)
            : $product->current_price);

        $existing = $cart->items()
            ->where('product_id', $data['product_id'])
            ->where('variant_id', $data['variant_id'] ?? null)
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $requestedQty;
            if ($newQty > $maxQty) {
                abort(400, "Maximum quantity per item is {$maxQty}.");
            }
            $existing->update(['quantity' => $newQty]);
            CartUpdated::dispatch($cart);
            return $existing->fresh();
        }

        $item = $cart->items()->create([
            'product_id'  => $data['product_id'],
            'variant_id'  => $data['variant_id'] ?? null,
            'quantity'    => $requestedQty,
            'unit_price'  => $unitPrice,
            'total_price' => $unitPrice * $requestedQty,
        ]);

        CartUpdated::dispatch($cart);

        return $item;
    }

    public function updateItem(CartItem $item, array $data): CartItem
    {
        $quantity = $data['quantity'] ?? 1;

        if ($quantity <= 0) {
            $item->delete();
            CartUpdated::dispatch($item->cart);
            return $item;
        }

        $maxQty = config('ecommerce.cart.max_quantity', 100);
        if ($quantity > $maxQty) {
            abort(400, "Maximum quantity per item is {$maxQty}.");
        }

        // Stock validation: check variant stock or product stock
        if ($item->variant_id) {
            $variant = $item->variant;
            if ($variant && $quantity > $variant->stock_quantity) {
                abort(400, "Only {$variant->stock_quantity} units available for this variant.");
            }
        } elseif ($item->product && $item->product->manage_stock) {
            if ($quantity > $item->product->stock_quantity) {
                abort(400, "Only {$item->product->stock_quantity} units available.");
            }
        }

        $item->update([
            'quantity'    => $quantity,
            'total_price' => $item->unit_price * $quantity,
        ]);

        CartUpdated::dispatch($item->cart);

        return $item->fresh();
    }

    public function removeItem(CartItem $item): void
    {
        $cart = $item->cart;
        $item->delete();
        CartUpdated::dispatch($cart);
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        CartUpdated::dispatch($cart);
    }

    public function applyCoupon(Cart $cart, string $couponCode): Cart
    {
        $cart->update([
            'coupon_code'     => $couponCode,
            'discount_amount' => 0,
        ]);

        CartUpdated::dispatch($cart);

        return $cart->fresh()->load('items.product', 'items.variant');
    }

    public function removeCoupon(Cart $cart): Cart
    {
        $cart->update([
            'coupon_code'     => null,
            'discount_amount' => 0,
        ]);

        CartUpdated::dispatch($cart);

        return $cart->fresh()->load('items.product', 'items.variant');
    }

    /**
     * Get cart items grouped by vendor for split checkout.
     */
    public function getItemsGroupedByVendor(Cart $cart): array
    {
        $cart->load('items.product.vendors');

        $groups = [];
        foreach ($cart->items as $item) {
            $vendorId = $item->product->vendors->first()?->id ?? 0;
            $vendorName = $item->product->vendors->first()?->shop_name ?? 'General';

            if (!isset($groups[$vendorId])) {
                $groups[$vendorId] = [
                    'vendor_id'   => $vendorId,
                    'vendor_name' => $vendorName,
                    'items'       => [],
                    'subtotal'    => 0,
                ];
            }

            $groups[$vendorId]['items'][] = $item;
            $groups[$vendorId]['subtotal'] += $item->total;
        }

        return array_values($groups);
    }

    /**
     * Merge guest cart into user cart on login.
     */
    public function mergeGuestCart(string $sessionId, User $user): Cart
    {
        $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();
        $userCart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['session_id' => Str::random(40)]
        );

        if (!$guestCart || $guestCart->id === $userCart->id) {
            return $userCart->load('items.product', 'items.variant');
        }

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('variant_id', $guestItem->variant_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
            } else {
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        $guestCart->delete();
        CartUpdated::dispatch($userCart);

        return $userCart->load('items.product', 'items.variant');
    }
}
