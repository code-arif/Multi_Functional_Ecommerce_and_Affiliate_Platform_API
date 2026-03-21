<?php

namespace App\Http\Controllers\API\V1\Review;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Services\ReviewService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;
    
    public function __construct(private ReviewService $reviewService) {}

    /**
     * GET /api/v1/products/{slug}/reviews
     */
    public function index(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $reviews = $product->reviews()
                           ->with('user:id,name,avatar')
                           ->orderBy('created_at', 'desc')
                           ->paginate(10);

        $stats = [
            'average' => round($product->average_rating, 1),
            'total'   => $product->total_reviews,
            'breakdown' => $product->allReviews()
                ->where('status', 'approved')
                ->selectRaw('rating, count(*) as count')
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->pluck('count', 'rating')
                ->toArray(),
        ];

        return $this->paginatedResponse(
            ReviewResource::collection($reviews),
            'Reviews fetched.'
        );
    }

    /**
     * POST /api/v1/reviews
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id'   => 'nullable|exists:orders,id',
            'rating'     => 'required|integer|min:1|max:5',
            'title'      => 'nullable|string|max:100',
            'body'       => 'nullable|string|max:2000',
            'images'     => 'nullable|array|max:3',
            'images.*'   => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->only(['product_id', 'order_id', 'rating', 'title', 'body']);

        // Handle review images
        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $img) {
                $paths[] = $img->store('reviews', 'public');
            }
            $data['images'] = $paths;
        }

        $review = $this->reviewService->createReview($request->user()->id, $data);

        return $this->createdResponse(
            new ReviewResource($review),
            'Review submitted and pending approval.'
        );
    }
}
