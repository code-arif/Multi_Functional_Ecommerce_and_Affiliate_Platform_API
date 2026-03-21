<?php

namespace App\Http\Controllers\API\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Services\CartService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;
    
    public function __construct(private CartService $cartService) {}

    /**
     * GET /api/v1/cart
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart(
            $request->user()?->id,
            $request->header('X-Session-ID')
        );

        if (!$cart) {
            return $this->successResponse([
                'id'              => null,
                'total_items'     => 0,
                'subtotal'        => 0,
                'discount_amount' => 0,
                'total'           => 0,
                'items'           => [],
                'coupon'          => null,
            ], 'Cart is empty.');
        }

        return $this->successResponse(
            new CartResource($cart->load('items.product.images', 'items.variant', 'coupon')),
            'Cart fetched successfully.'
        );
    }

    /**
     * POST /api/v1/cart/items
     */
    public function addItem(AddToCartRequest $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart(
            $request->user()?->id,
            $request->header('X-Session-ID') ?? $this->cartService->generateSessionId()
        );

        $item = $this->cartService->addItem($cart, $request->validated());

        $cart->refresh()->load('items.product.images', 'items.variant', 'coupon');

        return $this->successResponse(
            new CartResource($cart),
            'Item added to cart.'
        );
    }

    /**
     * PUT /api/v1/cart/items/{item}
     */
    public function updateItem(Request $request, CartItem $item): JsonResponse
    {
        $request->validate(['quantity' => 'required|integer|min:1|max:100']);

        $this->authorizeCartItem($request, $item);

        $this->cartService->updateItem($item, $request->quantity);

        $cart = $item->cart->refresh()->load('items.product.images', 'items.variant', 'coupon');

        return $this->successResponse(new CartResource($cart), 'Cart updated.');
    }

    /**
     * DELETE /api/v1/cart/items/{item}
     */
    public function removeItem(Request $request, CartItem $item): JsonResponse
    {
        $this->authorizeCartItem($request, $item);
        $cart = $item->cart;
        $this->cartService->removeItem($item);
        $cart->refresh()->load('items.product.images', 'items.variant', 'coupon');

        return $this->successResponse(new CartResource($cart), 'Item removed from cart.');
    }

    /**
     * DELETE /api/v1/cart
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart(
            $request->user()?->id,
            $request->header('X-Session-ID')
        );

        if ($cart) $this->cartService->clearCart($cart);

        return $this->noContentResponse('Cart cleared.');
    }

    /**
     * POST /api/v1/cart/coupon
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);

        $cart = $this->cartService->getCart(
            $request->user()?->id,
            $request->header('X-Session-ID')
        );

        if (!$cart) return $this->errorResponse('Cart not found.', null, 404);

        $result = $this->cartService->applyCoupon($cart, $request->code, $request->user()?->id);

        $cart->refresh()->load('items.product', 'items.variant', 'coupon');

        return $this->successResponse([
            'cart'            => new CartResource($cart),
            'discount_amount' => $result['discount_amount'],
            'coupon_code'     => $result['coupon']->code,
        ], 'Coupon applied successfully.');
    }

    /**
     * DELETE /api/v1/cart/coupon
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart(
            $request->user()?->id,
            $request->header('X-Session-ID')
        );

        if (!$cart) return $this->errorResponse('Cart not found.', null, 404);

        $this->cartService->removeCoupon($cart);

        return $this->noContentResponse('Coupon removed.');
    }

    private function authorizeCartItem(Request $request, CartItem $item): void
    {
        $userId    = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID');

        $isOwner = ($userId && $item->cart->user_id === $userId)
                || ($sessionId && $item->cart->session_id === $sessionId);

        if (!$isOwner) {
            abort(403, 'Unauthorized access to cart item.');
        }
    }
}
