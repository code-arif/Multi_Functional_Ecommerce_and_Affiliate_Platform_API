<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewHelpfulVote;
use Modules\Product\Models\Product;;
use App\Models\User;
use Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    // ─── CRUD ──────────────────────────────────────────────────────

    public function createReview(User $user, array $data): Review
    {
        $product = Product::findByUuidOrFail($data['product_uuid']);
        $order = !empty($data['order_uuid']) ? Order::findByUuidOrFail($data['order_uuid']) : null;

        $data['is_verified_purchase'] = $this->checkVerifiedPurchase($user->id, $product->id, $order?->id);

        $review = Review::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'order_id'   => $order?->id,
            'rating'     => $data['rating'],
            'title'      => $data['title'] ?? null,
            'body'       => $data['body'],
            'images'     => $data['images'] ?? [],
            'is_verified_purchase' => $data['is_verified_purchase'],
            'status'     => config('ecommerce.features.review_auto_approve', false) ? 'approved' : 'pending',
        ]);

        if ($review->status === 'approved') {
            $review->product->recalculateRating();
        }

        return $review;
    }

    public function updateReview(Review $review, User $user, array $data): Review
    {
        if ($review->user_id !== $user->id) {
            abort(403, 'You can only update your own reviews.');
        }

        if ($review->status === 'approved') {
            abort(400, 'Cannot update an approved review. Contact support.');
        }

        $review->update([
            'rating'  => $data['rating'] ?? $review->rating,
            'title'   => $data['title'] ?? $review->title,
            'body'    => $data['body'] ?? $review->body,
            'images'  => $data['images'] ?? $review->images,
        ]);

        return $review->fresh();
    }

    public function deleteReview(Review $review, User $user): void
    {
        if ($review->user_id !== $user->id && !$user->hasPermission('reviews.moderate')) {
            abort(403, 'You can only delete your own reviews.');
        }

        $product = $review->product;
        $review->delete();

        if ($product) {
            $product->recalculateRating();
        }
    }

    // ─── Helpful Votes ─────────────────────────────────────────────

    public function markHelpful(Review $review, User $user): array
    {
        $existing = ReviewHelpfulVote::where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return [
                'action'  => 'already_voted',
                'helpful' => true,
                'count'   => $review->helpful_count,
            ];
        }

        DB::transaction(function () use ($review, $user) {
            ReviewHelpfulVote::create([
                'review_id' => $review->id,
                'user_id'   => $user->id,
            ]);

            $review->increment('helpful_count');
        });

        Log::info('Review marked helpful', [
            'review_id' => $review->id,
            'user_id'   => $user->id,
        ]);

        return [
            'action'  => 'voted',
            'helpful' => true,
            'count'   => $review->fresh()->helpful_count,
        ];
    }

    public function unmarkHelpful(Review $review, User $user): array
    {
        $existing = ReviewHelpfulVote::where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$existing) {
            return [
                'action'  => 'not_voted',
                'helpful' => false,
                'count'   => $review->helpful_count,
            ];
        }

        DB::transaction(function () use ($review, $existing) {
            $existing->delete();
            $review->decrement('helpful_count');
        });

        return [
            'action'  => 'unvoted',
            'helpful' => false,
            'count'   => $review->fresh()->helpful_count,
        ];
    }

    // ─── Vendor Responses ──────────────────────────────────────────

    public function respondToReview(Review $review, User $vendorUser, string $response): Review
    {
        $vendor = $vendorUser->vendor;
        if (!$vendor) {
            abort(403, 'Only vendors can respond to reviews.');
        }

        $product = $review->product;
        if (!$product || $product->vendor_id !== $vendor->id) {
            abort(403, 'You can only respond to reviews for your own products.');
        }

        $review->update([
            'vendor_response'     => $response,
            'vendor_responded_at' => now(),
        ]);

        Log::info('Vendor responded to review', [
            'review_id' => $review->id,
            'vendor_id' => $vendor->id,
        ]);

        return $review->fresh();
    }

    // ─── Queries ───────────────────────────────────────────────────

    public function getUserReviews(User $user, array $filters = [])
    {
        $query = Review::with('product')
            ->where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function getProductReviews(Product $product, array $filters = [])
    {
        $query = $product->reviews()->with('user');

        // Apply filters
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['has_images'])) {
            $query->where(function ($q) {
                $q->whereNotNull('images')->where('images', '!=', '[]');
            });
        }
        if (!empty($filters['verified_only'])) {
            $query->where('is_verified_purchase', true);
        }
        if (!empty($filters['has_vendor_response'])) {
            $query->whereNotNull('vendor_response');
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'newest'          => $query->latest(),
            'oldest'          => $query->oldest(),
            'highest'         => $query->orderBy('rating', 'desc'),
            'lowest'          => $query->orderBy('rating', 'asc'),
            'most_helpful'    => $query->orderBy('helpful_count', 'desc'),
            default           => $query->latest(),
        };

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function getProductReviewStats(Product $product): array
    {
        $allReviews = $product->allReviews()->select('rating', 'status', 'is_verified_purchase')->get();

        $approved = $allReviews->where('status', 'approved');

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $approved->where('rating', $i)->count();
            $distribution[$i] = [
                'count'  => $count,
                'percent' => $approved->count() > 0
                    ? round(($count / $approved->count()) * 100, 1)
                    : 0,
            ];
        }

        return [
            'average_rating'     => (float) $product->average_rating,
            'total_reviews'      => (int) $product->total_reviews,
            'total_pending'      => $allReviews->where('status', 'pending')->count(),
            'total_rejected'     => $allReviews->where('status', 'rejected')->count(),
            'verified_count'     => $approved->where('is_verified_purchase', true)->count(),
            'with_images_count'  => $allReviews->where('status', 'approved')
                ->filter(fn($r) => !empty($r->images))->count(),
            'distribution'       => $distribution,
        ];
    }

    // ─── Moderation ────────────────────────────────────────────────

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

    public function getPendingReviewsCount(): int
    {
        return Review::where('status', 'pending')->count();
    }

    // ─── Helpers ───────────────────────────────────────────────────

    /**
     * Check if a user has purchased this product (verified purchase).
     */
    private function checkVerifiedPurchase(int $userId, int $productId, ?int $orderId = null): bool
    {
        if ($orderId) {
            return Order::where('id', $orderId)
                ->where('user_id', $userId)
                ->where('status', 'delivered')
                ->whereHas('items', fn($q) => $q->where('product_id', $productId))
                ->exists();
        }

        // Check all completed orders for this product
        return Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereHas('items', fn($q) => $q->where('product_id', $productId))
            ->exists();
    }
}
