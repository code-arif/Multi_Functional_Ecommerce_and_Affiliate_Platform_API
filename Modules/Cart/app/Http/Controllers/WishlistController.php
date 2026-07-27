<?php

namespace Modules\Cart\Http\Controllers;

use Modules\Cart\Services\WishlistService;
use Modules\Cart\Services\CartService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController
{
    use ApiResponse;

    public function __construct(
        private WishlistService $wishlistService,
        private CartService $cartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $wishlist = $this->wishlistService->getUserWishlist($request->user());
        return $this->successResponse($wishlist);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate(['product_id' => 'required|exists:products,id']);
        $result = $this->wishlistService->toggle($request->user(), $validated['product_id']);

        return $this->successResponse(
            ['wishlisted' => $result['wishlisted']],
            $result['message']
        );
    }

    public function moveToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'integer|min:1',
        ]);

        $cart = $this->cartService->getCart($request->user(), null);
        $this->cartService->addItem($cart, $validated);

        // Remove from wishlist
        $this->wishlistService->toggle($request->user(), $validated['product_id']);

        return $this->successResponse(null, 'Item moved to cart.');
    }
}
