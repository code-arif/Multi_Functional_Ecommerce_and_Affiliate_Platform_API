<?php

namespace Modules\Reviews\Http\Controllers;

use Modules\Reviews\Services\ReviewService;
use Modules\Catalog\Models\Product;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController
{
    use ApiResponse;

    public function __construct(private ReviewService $reviewService) {}

    public function index(string $productSlug): JsonResponse
    {
        $product = Product::where('slug', $productSlug)->firstOrFail();
        $reviews = $product->reviews()
            ->with('user')
            ->latest()
            ->paginate(config('ecommerce.pagination.reviews_per_page', 10));

        return $this->paginatedResponse($reviews);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id'   => 'nullable|exists:orders,id',
            'rating'     => 'required|integer|min:1|max:5',
            'title'      => 'nullable|string|max:100',
            'body'       => 'required|string|max:5000',
            'images'     => 'nullable|array',
            'images.*'   => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $review = $this->reviewService->createReview($request->user(), $validated);

        return $this->createdResponse($review, 'Review submitted.');
    }
}
