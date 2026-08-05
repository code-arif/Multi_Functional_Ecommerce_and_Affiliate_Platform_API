<?php

namespace Modules\Orders\Http\Controllers\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Core\Traits\ApiResponse;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Orders\Models\Order;

class TrackingController
{
    use ApiResponse;

    /**
     * GET /api/v1/orders/track/{token} — public guest tracking.
     */
    public function trackGuest(string $token): JsonResponse
    {
        $order = Order::with(['items', 'statusHistories', 'vendor'])
            ->where('tracking_token', $token)
            ->first();

        if (!$order) {
            return $this->errorResponse('Invalid tracking token.', null, 404);
        }

        return $this->successResponse(new OrderResource($order), 'Order found.');
    }
}
