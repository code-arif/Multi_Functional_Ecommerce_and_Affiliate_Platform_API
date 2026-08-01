<?php

namespace Modules\Reviews\Http\Controllers;

use Modules\Reviews\Services\ReviewService;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Http\Resources\ReviewResource;
use Modules\Catalog\Models\Product;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorReviewController
{
    use ApiResponse;

    public function __construct(private ReviewService $reviewService) {}

    /**
     * GET /api/v1/vendor/reviews
     * List reviews for vendor's products.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) return $this->errorResponse('Not a vendor.', null, 403);

        $productIds = Product::where('vendor_id', $vendor->id)->pluck('id');

        $query = Review::with('user')
            ->whereIn('product_id', $productIds)
            ->approved();

        // Filters
        if ($request->rating) {
            $query->where('rating', $request->rating);
        }
        if ($request->product_uuid) {
            $query->where('product_id', Product::findByUuid($request->product_uuid)?->id);
        }
        if ($request->has_vendor_response === 'true') {
            $query->whereNotNull('vendor_response');
        } elseif ($request->has_vendor_response === 'false') {
            $query->whereNull('vendor_response');
        }
        if ($request->sort === 'newest') {
            $query->latest();
        } elseif ($request->sort === 'oldest') {
            $query->oldest();
        }

        $reviews = $query->paginate($request->per_page ?? 15);

        return $this->paginatedResponse(ReviewResource::collection($reviews));
    }

    /**
     * POST /api/v1/vendor/reviews/{review}/respond
     * Vendor responds to a review.
     */
    public function respond(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'response' => 'required|string|max:2000',
        ]);

        $review = $this->reviewService->respondToReview($review, $request->user(), $validated['response']);

        return $this->successResponse(
            new ReviewResource($review->load('user')),
            'Response submitted.'
        );
    }
}
