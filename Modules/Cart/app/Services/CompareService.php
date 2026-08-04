<?php

namespace Modules\Cart\Services;

use Modules\Cart\Models\CompareList;
use Modules\Cart\Models\CompareListItem;
use Modules\Auth\Models\User;
use Modules\Product\Models\Product;

class CompareService
{
    /**
     * Get or create a compare list for user/session.
     */
    public function getCompareList(?User $user = null, ?string $sessionId = null): CompareList
    {
        if ($user) {
            return CompareList::firstOrCreate(['user_id' => $user->id]);
        }

        if ($sessionId) {
            return CompareList::firstOrCreate(['session_id' => $sessionId]);
        }

        return CompareList::create(['session_id' => uniqid('cmp_', true)]);
    }

    /**
     * Add a product to the compare list.
     */
    public function add(CompareList $list, string $productUuid): array
    {
        $product = \Modules\Product\Models\Product::findByUuidOrFail($productUuid);
        $productId = $product->id;

        if ($list->items()->where('product_id', $productId)->exists()) {
            return ['success' => false, 'message' => 'Product already in compare list.'];
        }

        // Limit to 10 products max
        if ($list->items()->count() >= 10) {
            return ['success' => false, 'message' => 'Compare list is full (max 10 products).'];
        }

        $list->items()->create(['product_id' => $productId]);

        return ['success' => true, 'message' => 'Added to compare list.'];
    }

    /**
     * Remove a product from the compare list.
     */
    public function remove(CompareList $list, string $productUuid): array
    {
        $productId = \Modules\Product\Models\Product::findByUuidOrFail($productUuid)->id;
        $list->items()->where('product_id', $productId)->delete();

        return ['success' => true, 'message' => 'Removed from compare list.'];
    }

    /**
     * Get compare list with products loaded.
     */
    public function getWithProducts(CompareList $list)
    {
        return $list->load(['items.product.category', 'items.product.brand', 'items.product.primaryImage']);
    }

    /**
     * Clear the compare list.
     */
    public function clear(CompareList $list): void
    {
        $list->items()->delete();
    }

    /**
     * Merge guest compare list into user account on login.
     */
    public function mergeSessionIntoUser(string $sessionId, User $user): void
    {
        $guestList = CompareList::where('session_id', $sessionId)->whereNull('user_id')->first();
        if (!$guestList) return;

        $userList = CompareList::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestList->items as $item) {
            if (!$userList->items()->where('product_id', $item->product_id)->exists()) {
                $userList->items()->create(['product_id' => $item->product_id]);
            }
        }

        // Delete the old guest list
        $guestList->delete();
    }
}
