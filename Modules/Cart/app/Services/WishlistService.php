<?php

namespace Modules\Cart\Services;

use \Modules\Product\Models\Product;
use App\Models\User;
use Modules\Catalog\Models\Wishlist;

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
        $productId = Product::findByUuidOrFail($productUuid)->id;

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
