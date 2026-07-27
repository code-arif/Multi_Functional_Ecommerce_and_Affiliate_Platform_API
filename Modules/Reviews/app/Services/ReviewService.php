<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Models\Review;
use Modules\Catalog\Models\Product;
use Modules\Auth\Models\User;

class ReviewService
{
    public function createReview(User $user, array $data): Review
    {
        $review = Review::create([
            'user_id'    => $user->id,
            'product_id' => $data['product_id'],
            'order_id'   => $data['order_id'] ?? null,
            'rating'     => $data['rating'],
            'title'      => $data['title'] ?? null,
            'body'       => $data['body'],
            'images'     => $data['images'] ?? [],
            'status'     => config('ecommerce.features.review_auto_approve', false) ? 'approved' : 'pending',
        ]);

        if ($review->status === 'approved') {
            $review->product->recalculateRating();
        }

        return $review;
    }

    public function approveReview(Review $review): Review
    {
        $review->update(['status' => 'approved']);
        if ($review->product) {
            $review->product->recalculateRating();
        }
        return $review->fresh();
    }

    public function rejectReview(Review $review): Review
    {
        $review->update(['status' => 'rejected']);
        return $review->fresh();
    }
}
