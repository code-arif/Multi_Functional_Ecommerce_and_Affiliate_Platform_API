<?php

namespace Modules\Vendor\Http\Controllers;

use \Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\Reviews\Http\Resources\ReviewResource;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewService;

class VendorReviewController
{
    use ApiResponse;

    public function __construct(private ReviewService $reviewService) {}

    /**
     * GET /api/v1/vendor/reviews
     * List reviews for the authenticated vendor's products.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $reviews = Review::with(['user', 'product'])
            ->whereHas('product.vendorProductPrices', fn($q) => $q->where('vendor_id', $vendor->id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->rating, fn($q) => $q->where('rating', $request->rating))
            ->when($request->product_uuid, fn($q) => $q->where('product_id', Product::findByUuid($request->product_uuid)?->id))
            ->when($request->search, fn($q) => $q->whereHas('product', fn($q) => $q->where('name', 'like', "%{$request->search}%")))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(ReviewResource::collection($reviews));
    }

    /**
     * POST /api/v1/vendor/reviews/{review}/reply
     * Reply to a review on the vendor's product.
     */
    public function reply(Request $request, Review $review): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $validated = $request->validate([
            'response' => 'required|string|max:2000',
        ]);

        // Verify this review is for the vendor's product
        if (!$review->product) {
            return $this->errorResponse('The reviewed product no longer exists.', null, 404);
        }

        $belongsToVendor = $review->product
            ->vendorProductPrices()
            ->where('vendor_id', $vendor->id)
            ->exists();

        if (!$belongsToVendor) {
            return $this->errorResponse('You can only respond to reviews for your own products.', null, 403);
        }

        $review->update([
            'vendor_response'     => $validated['response'],
            'vendor_responded_at' => now(),
        ]);

        return $this->successResponse(
            new ReviewResource($review->fresh()->load('user')),
            'Response submitted.'
        );
    }
}
