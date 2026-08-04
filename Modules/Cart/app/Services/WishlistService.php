<?php

namespace Modules\Cart\Services;

use Modules\Catalog\Models\Wishlist;
use Modules\Auth\Models\User;

class WishlistService
{
    public function getUserWishlist(User $user)
    {
        return Wishlist::with('product')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function toggle(User $user, string $productUuid): array
    {
        $productId = \Modules\Product\Models\Product;::findByUuidOrFail($productUuid)->id;

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return ['wishlisted' => false, 'message' => 'Removed from wishlist.'];
        }

        Wishlist::create([
            'user_id'    => $user->id,
            'product_id' => $productId,
        ]);

        return ['wishlisted' => true, 'message' => 'Added to wishlist.'];
    }
}
