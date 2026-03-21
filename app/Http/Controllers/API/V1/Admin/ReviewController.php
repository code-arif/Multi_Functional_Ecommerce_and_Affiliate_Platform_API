<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function index(Request $request): JsonResponse
    {
        $reviews = Review::with(['product:id,name,slug', 'user:id,name'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return $this->paginatedResponse(ReviewResource::collection($reviews));
    }

    public function approve(Review $review): JsonResponse
    {
        $this->reviewService->approveReview($review);
        return $this->successResponse(null, 'Review approved.');
    }

    public function reject(Review $review): JsonResponse
    {
        $this->reviewService->rejectReview($review);
        return $this->successResponse(null, 'Review rejected.');
    }

    public function destroy(Review $review): JsonResponse
    {
        $this->reviewService->deleteReview($review);
        return $this->noContentResponse('Review deleted.');
    }
}
