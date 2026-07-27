<?php

namespace Modules\Cart\Http\Controllers;

use Modules\Cart\Services\CartService;
use Modules\Cart\Http\Resources\CartResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController
{
    use ApiResponse;

    public function __construct(private CartService $cartService) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), $request->header('X-Session-ID'));
        return $this->successResponse(new CartResource($cart));
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity'   => 'integer|min:1|max:' . config('ecommerce.cart.max_quantity', 100),
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        $cart = $this->cartService->getCart($request->user(), $request->header('X-Session-ID'));

        if ($request->user()) {
            $cart->update(['user_id' => $request->user()->id]);
        }

        $item = $this->cartService->addItem($cart, $validated);

        return $this->createdResponse(new CartResource($cart->fresh()->load('items.product', 'items.variant')));
    }

    public function updateItem(int $item, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:' . config('ecommerce.cart.max_quantity', 100),
        ]);

        $cart = $this->cartService->getCart($request->user(), $request->header('X-Session-ID'));
        $cartItem = $cart->items()->findOrFail($item);

        $this->cartService->updateItem($cartItem, $validated);

        return $this->successResponse(
            new CartResource($cart->fresh()->load('items.product', 'items.variant')),
            'Cart updated.'
        );
    }

    public function removeItem(int $item, Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), $request->header('X-Session-ID'));
        $cartItem = $cart->items()->findOrFail($item);
        $this->cartService->removeItem($cartItem);

        return $this->noContentResponse('Item removed from cart.');
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), $request->header('X-Session-ID'));
        $this->cartService->clearCart($cart);

        return $this->noContentResponse('Cart cleared.');
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => 'required|string|max:50']);

        $cart = $this->cartService->getCart($request->user(), $request->header('X-Session-ID'));
        $cart = $this->cartService->applyCoupon($cart, $validated['code']);

        return $this->successResponse(new CartResource($cart), 'Coupon applied.');
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), $request->header('X-Session-ID'));
        $cart = $this->cartService->removeCoupon($cart);

        return $this->successResponse(new CartResource($cart), 'Coupon removed.');
    }
}
