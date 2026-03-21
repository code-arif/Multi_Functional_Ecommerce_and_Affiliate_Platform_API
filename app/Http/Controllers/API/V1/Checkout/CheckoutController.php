<?php

namespace App\Http\Controllers\API\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use ApiResponse;
    
    public function __construct(private CheckoutService $checkoutService) {}

    /**
     * POST /api/v1/checkout
     */
    public function process(CheckoutRequest $request): JsonResponse
    {
        $order = $this->checkoutService->process(
            $request->validated(),
            $request->user()?->id,
            $request->header('X-Session-ID')
        );

        return $this->createdResponse(
            new OrderResource($order),
            'Order placed successfully.'
        );
    }

    /**
     * GET /api/v1/checkout/shipping-cost
     */
    public function shippingCost(Request $request): JsonResponse
    {
        $request->validate(['subtotal' => 'required|numeric|min:0']);

        $freeShippingOver = (float) \App\Models\Setting::get('free_shipping_over', 1000);
        $shippingCharge   = (float) \App\Models\Setting::get('shipping_charge', 60);

        $charge  = $request->subtotal >= $freeShippingOver ? 0 : $shippingCharge;

        return $this->successResponse([
            'shipping_charge'    => $charge,
            'free_shipping_over' => $freeShippingOver,
            'is_free_shipping'   => $charge === 0.0,
        ]);
    }
}
