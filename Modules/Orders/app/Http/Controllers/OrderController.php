<?php

namespace Modules\Orders\Http\Controllers;

use Modules\Orders\Services\OrderService;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController
{
    use ApiResponse;

    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getUserOrders($request->user(), $request->only(['status', 'per_page']));
        return $this->paginatedResponse(OrderResource::collection($orders));
    }

    public function show(string $number, Request $request): JsonResponse
    {
        $order = $this->orderService->getOrderByNumber($number, $request->user());
        return $this->successResponse(new OrderResource($order));
    }

    public function cancel(string $number, Request $request): JsonResponse
    {
        $order = $this->orderService->getOrderByNumber($number, $request->user());

        $validated = $request->validate(['reason' => 'nullable|string|max:500']);
        $order = $this->orderService->cancelOrder($order, $validated['reason'] ?? null);

        return $this->successResponse(new OrderResource($order), 'Order cancelled.');
    }

    /**
     * POST /api/v1/orders/{number}/cancel-request
     * Submit a cancellation request for admin review.
     */
    public function requestCancel(string $number, Request $request): JsonResponse
    {
        $order = $this->orderService->getOrderByNumber($number, $request->user());

        $validated = $request->validate(['reason' => 'required|string|max:500']);
        $cancelRequest = $this->orderService->requestCancellation($order, $request->user(), $validated['reason']);

        return $this->successResponse($cancelRequest, 'Cancellation request submitted.');
    }

    public function trackGuest(string $token): JsonResponse
    {
        $order = $this->orderService->getOrderByToken($token);
        return $this->successResponse(new OrderResource($order));
    }
}
