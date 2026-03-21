<?php

namespace App\Services;

use App\Models\Wishlist;
use App\Models\Product;

class WishlistService
{
    public function getWishlist(int $userId)
    {
        return Wishlist::where('user_id', $userId)
                       ->with(['product.images', 'product.category'])
                       ->get();
    }

    public function toggle(int $userId, int $productId): array
    {
        Product::findOrFail($productId);

        $existing = Wishlist::where('user_id', $userId)
                            ->where('product_id', $productId)
                            ->first();

        if ($existing) {
            $existing->delete();
            return ['action' => 'removed', 'in_wishlist' => false];
        }

        Wishlist::create(['user_id' => $userId, 'product_id' => $productId]);
        return ['action' => 'added', 'in_wishlist' => true];
    }

    public function isInWishlist(int $userId, int $productId): bool
    {
        return Wishlist::where('user_id', $userId)
                       ->where('product_id', $productId)
                       ->exists();
    }

    public function moveToCart(int $userId, int $productId): void
    {
        $cart        = app(CartService::class)->getOrCreateCart($userId, null);
        $wishlistItem = Wishlist::where('user_id', $userId)
                                ->where('product_id', $productId)
                                ->firstOrFail();

        app(CartService::class)->addItem($cart, [
            'product_id' => $productId,
            'quantity'   => 1,
        ]);

        $wishlistItem->delete();
    }
}
