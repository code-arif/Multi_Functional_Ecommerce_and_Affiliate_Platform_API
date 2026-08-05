<?php

namespace Modules\Checkout\Http\Controllers;

use Modules\Checkout\Services\CheckoutService;
use Modules\Checkout\Http\Requests\CheckoutRequest;
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

    /**
     * POST /api/v1/checkout/preview
     * Get order preview/summary before placing order.
     */
    public function preview(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), null);

        if ($cart->isEmpty) {
            return $this->errorResponse('Your cart is empty.', null, 400);
        }

        $preview = $this->checkoutService->preview(
            $cart,
            $request->user(),
            $request->only(['address_uuid', 'shipping_address', 'shipping_method'])
        );

        return $this->successResponse($preview, 'Order preview generated.');
    }

    /**
     * POST /api/v1/checkout
     * Place an order (process checkout).
     */
    public function process(CheckoutRequest $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), null);

        if ($cart->isEmpty) {
            return $this->errorResponse('Your cart is empty.', null, 400);
        }

        $orders = $this->checkoutService->processCheckout(
            $cart,
            $request->user(),
            $request->validated()
        );

        return $this->createdResponse([
            'orders'         => collect($orders)->map(fn($o) => [
                'order_number'  => $o->order_number,
                'group_id'      => $o->group_id,
                'total'         => $o->total_amount,
                'tracking_token' => $o->tracking_token,
            ]),
            'message' => count($orders) > 1
                ? count($orders) . ' orders placed successfully.'
                : 'Order placed successfully.',
        ]);
    }

    /**
     * GET /api/v1/checkout/shipping-options
     * Get available shipping methods per vendor group.
     */
    public function shippingOptions(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), null);

        if ($cart->isEmpty) {
            return $this->errorResponse('Your cart is empty.', null, 400);
        }

        $options = $this->checkoutService->getShippingOptions($cart);

        return $this->successResponse($options);
    }

    /**
     * POST /api/v1/checkout/shipping-cost
     * Calculate shipping cost.
     */
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

    /**
     * POST /api/v1/checkout/calculate-tax
     * Calculate tax for the current cart.
     */
    public function calculateTax(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user(), null);

        $validated = $request->validate([
            'country' => 'nullable|string|max:100',
            'state'   => 'nullable|string|max:100',
        ]);

        $taxAmount = $this->checkoutService->calculateTax(
            $cart->subtotal,
            $validated
        );

        return $this->successResponse([
            'subtotal'  => $cart->subtotal,
            'tax_rate'  => $this->getTaxRateForCountry($validated['country'] ?? ''),
            'tax_amount' => $taxAmount,
        ]);
    }

    private function getTaxRateForCountry(?string $country): float
    {
        $rates = [
            'Bangladesh' => 0.05,
            'BD'         => 0.05,
            'India'      => 0.18,
            'USA'        => 0.0,
            'US'         => 0.0,
            'UK'         => 0.20,
            'GB'         => 0.20,
        ];
        return $rates[$country ?? ''] ?? 0.0;
    }
}
