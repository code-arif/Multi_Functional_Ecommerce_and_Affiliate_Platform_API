<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Modules\Reviews\Models\Review;

class ReviewPolicy
{
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create a review
    }

    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id || $user->hasPermission('reviews.moderate');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('reviews.moderate');
    }

    public function reject(User $user): bool
    {
        return $user->hasPermission('reviews.moderate');
    }

    public function respond(User $user, Review $review): bool
    {
        // Vendor who owns the product can respond
        $vendor = $user->vendor;
        if (!$vendor) return false;

        $product = $review->product;
        if (!$product) return false;

        return $product->vendor_id === $vendor->id;
    }
}
