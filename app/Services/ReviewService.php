<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Order;
use App\Models\Product;

class ReviewService
{
    public function createReview(int $userId, array $data): Review
    {
        $product = Product::findOrFail($data['product_id']);

        // Check if user has already reviewed this product for this order
        $existing = Review::where('user_id', $userId)
                          ->where('product_id', $data['product_id'])
                          ->where('order_id', $data['order_id'] ?? null)
                          ->exists();

        if ($existing) {
            throw new \Exception('You have already reviewed this product.');
        }

        // Verify purchase
        $isVerified = false;
        if (!empty($data['order_id'])) {
            $isVerified = Order::where('id', $data['order_id'])
                               ->where('user_id', $userId)
                               ->whereHas('items', fn($q) => $q->where('product_id', $data['product_id']))
                               ->exists();
        }

        $review = Review::create([
            'product_id'           => $data['product_id'],
            'user_id'              => $userId,
            'order_id'             => $data['order_id'] ?? null,
            'rating'               => $data['rating'],
            'title'                => $data['title'] ?? null,
            'body'                 => $data['body'] ?? null,
            'images'               => $data['images'] ?? null,
            'status'               => 'pending',
            'is_verified_purchase' => $isVerified,
        ]);

        return $review;
    }

    public function approveReview(Review $review): Review
    {
        $review->update(['status' => 'approved']);
        $review->product->recalculateRating();
        return $review;
    }

    public function rejectReview(Review $review): Review
    {
        $review->update(['status' => 'rejected']);
        return $review;
    }

    public function deleteReview(Review $review): void
    {
        $product = $review->product;
        $review->delete();
        $product->recalculateRating();
    }
}
