<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Product\Models\Product;;

class ProductPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * View a product. Active products are public; pending/draft only visible to owner/admin.
     */
    public function view(?User $user, Product $product): bool
    {
        // Active products are public
        if ($product->status === 'active') {
            return true;
        }

        // Admin can view all products
        if ($user && $user->hasPermission('products.view')) {
            return true;
        }

        // Vendor can view their own products (via vendor_product_prices)
        if ($user && $user->vendor) {
            return $product->vendorProductPrices()
                ->where('vendor_id', $user->vendor->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Admin can always create
        if ($user->hasPermission('products.create')) {
            return true;
        }

        // Vendor with permission can create
        return $user->vendor && $user->isVendor();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('products.edit');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete');
    }

    /**
     * Admin can approve pending products.
     */
    public function approve(User $user): bool
    {
        return $user->hasPermission('products.approve') || $user->isAdmin();
    }
}
