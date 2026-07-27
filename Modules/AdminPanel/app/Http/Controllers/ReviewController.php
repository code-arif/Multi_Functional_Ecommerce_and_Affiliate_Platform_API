<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Reviews\Models\Review;
use Modules\Reviews\Http\Resources\ReviewResource;
use Modules\Reviews\Services\ReviewService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController
{
    use ApiResponse;

    public function __construct(private ReviewService $reviewService) {}

    public function index(Request $request): JsonResponse
    {
        $reviews = Review::with(['user', 'product'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->rating, fn($q) => $q->where('rating', $request->rating))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(ReviewResource::collection($reviews));
    }

    public function approve(Review $review): JsonResponse
    {
        $this->reviewService->approveReview($review);
        return $this->successResponse($review->fresh(), 'Review approved.');
    }

    public function reject(Review $review): JsonResponse
    {
        $this->reviewService->rejectReview($review);
        return $this->successResponse($review->fresh(), 'Review rejected.');
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();
        return $this->noContentResponse('Review deleted.');
    }
}
