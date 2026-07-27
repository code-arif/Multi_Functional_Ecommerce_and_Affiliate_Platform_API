<?php

namespace Modules\Checkout\Http\Controllers;

use Modules\Checkout\Services\CheckoutService;
use Modules\Cart\Services\CartService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController
{
    use ApiResponse;

    public function __construct(
        private CheckoutService $checkoutService,
        private CartService $cartService
    ) {}

    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address' => 'required',
            'billing_address'  => 'sometimes',
            'shipping_method'  => 'nullable|string|max:50',
            'payment_method'   => 'nullable|string|max:50|in:cod,stripe',
            'notes'            => 'nullable|string|max:1000',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'tax_amount'       => 'nullable|numeric|min:0',
        ]);

        $cart = $this->cartService->getCart($request->user(), null);

        if ($cart->isEmpty) {
            return $this->errorResponse('Your cart is empty.', null, 400);
        }

        $order = $this->checkoutService->processCheckout($cart, $request->user(), $validated);

        return $this->createdResponse([
            'order_number'  => $order->order_number,
            'total'         => $order->total,
            'tracking_token' => $order->tracking_token,
        ], 'Order placed successfully.');
    }

    public function shippingCost(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), null);

        $cost = $this->checkoutService->calculateShippingCost(
            $cart->items->toArray(),
            $request->address ?? '',
            $request->method ?? 'standard'
        );

        return $this->successResponse([
            'shipping_cost' => $cost,
            'free_over'     => config('ecommerce.shipping.free_over', 1000),
        ]);
    }
}
