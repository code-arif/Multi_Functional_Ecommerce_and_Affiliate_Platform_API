<?php

namespace Modules\Reviews\Http\Controllers;

use Modules\Reviews\Services\ReviewService;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Http\Resources\ReviewResource;
use Modules\Catalog\Models\Product;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController
{
    use ApiResponse;

    public function __construct(private ReviewService $reviewService) {}

    /**
     * GET /api/v1/products/{slug}/reviews
     * Public — list approved reviews for a product with filters.
     */
    public function index(string $productSlug, Request $request): JsonResponse
    {
        $product = Product::where('slug', $productSlug)->firstOrFail();
        $reviews = $this->reviewService->getProductReviews($product, $request->only([
            'rating', 'sort', 'has_images', 'verified_only', 'has_vendor_response', 'per_page',
        ]));

        return $this->paginatedResponse(ReviewResource::collection($reviews));
    }

    /**
     * GET /api/v1/products/{slug}/reviews/stats
     * Public — get review statistics for a product.
     */
    public function stats(string $productSlug): JsonResponse
    {
        $product = Product::where('slug', $productSlug)->firstOrFail();
        $stats = $this->reviewService->getProductReviewStats($product);

        return $this->successResponse($stats);
    }

    /**
     * POST /api/v1/reviews
     * Auth — create a new review with optional image uploads.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_uuid' => 'required|exists:products,uuid',
            'order_uuid'   => 'nullable|exists:orders,uuid',
            'rating'     => 'required|integer|min:1|max:5',
            'title'      => 'nullable|string|max:100',
            'body'       => 'required|string|max:5000',
            'images'     => 'nullable|array',
            'images.*'   => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $imagePaths[] = $path;
            }
        }
        $validated['images'] = $imagePaths;

        $review = $this->reviewService->createReview($request->user(), $validated);

        return $this->createdResponse(
            new ReviewResource($review->load('user')),
            'Review submitted.'
        );
    }

    /**
     * GET /api/v1/reviews/mine
     * Auth — get the authenticated user's reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $reviews = $this->reviewService->getUserReviews($request->user(), $request->only(['status', 'per_page']));

        return $this->paginatedResponse(ReviewResource::collection($reviews));
    }

    /**
     * PUT /api/v1/reviews/{review}
     * Auth — update own review (only if not yet approved).
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'title'  => 'nullable|string|max:100',
            'body'   => 'nullable|string|max:5000',
            'images' => 'nullable|array',
        ]);

        $review = $this->reviewService->updateReview($review, $request->user(), $validated);

        return $this->successResponse(new ReviewResource($review), 'Review updated.');
    }

    /**
     * DELETE /api/v1/reviews/{review}
     * Auth — delete own review (or admin/moderator).
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        $this->reviewService->deleteReview($review, $request->user());

        return $this->successResponse(null, 'Review deleted.');
    }

    /**
     * POST /api/v1/reviews/{review}/helpful
     * Auth — mark a review as helpful.
     */
    public function helpful(Request $request, Review $review): JsonResponse
    {
        $result = $this->reviewService->markHelpful($review, $request->user());

        return $this->successResponse($result, 'Thank you for your feedback.');
    }

    /**
     * DELETE /api/v1/reviews/{review}/helpful
     * Auth — remove helpful vote.
     */
    public function unhelpful(Request $request, Review $review): JsonResponse
    {
        $result = $this->reviewService->unmarkHelpful($review, $request->user());

        return $this->successResponse($result, 'Vote removed.');
    }
}
