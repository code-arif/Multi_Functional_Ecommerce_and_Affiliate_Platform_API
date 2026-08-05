<?php

namespace Modules\Orders\Http\Controllers\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Core\Traits\ApiResponse;
use Modules\Orders\Http\Requests\StoreCancelRequestRequest;
use Modules\Orders\Http\Resources\CancelRequestResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\CancelRequestService;

class CancelRequestController
{
    use ApiResponse;

    public function __construct(private CancelRequestService $cancelRequestService)
    {
    }

    /**
     * POST /api/v1/orders/{orderNumber}/cancel-request
     */
    public function store(StoreCancelRequestRequest $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return $this->errorResponse('No order found.', null, 404);
        }

        try {
            $cancelRequest = $this->cancelRequestService->request($order, $request->user(), $request->validated('reason'));

            return $this->createdResponse(
                new CancelRequestResource($cancelRequest->load('order')),
                'Cancellation request submitted successfully.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }
}
