<?php

namespace App\Http\Controllers\API\V1\Wishlist;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use App\Services\WishlistService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    use ApiResponse;
    
    public function __construct(private WishlistService $wishlistService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->wishlistService->getWishlist($request->user()->id);
        $products = $items->map(fn($w) => $w->product)->filter();
        return $this->successResponse(
            ProductListResource::collection($products),
            'Wishlist fetched successfully.'
        );
    }

    public function toggle(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $result = $this->wishlistService->toggle($request->user()->id, $request->product_id);
        return $this->successResponse($result, 'Wishlist updated.');
    }

    public function moveToCart(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $this->wishlistService->moveToCart($request->user()->id, $request->product_id);
        return $this->successResponse(null, 'Product moved to cart.');
    }
}
