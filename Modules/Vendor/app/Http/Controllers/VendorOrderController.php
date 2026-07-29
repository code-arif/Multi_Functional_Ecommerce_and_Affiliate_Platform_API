<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderService;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorOrderController
{
    use ApiResponse;

    public function __construct(private OrderService $orderService) {}

    /**
     * GET /api/v1/vendor/orders
     * List orders for the authenticated vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }
        if ($vendor->status !== 'active') {
            return $this->errorResponse('Your vendor account is not active.', null, 403);
        }

        $orders = $this->orderService->getVendorOrders($vendor->id, $request->only([
            'status', 'search', 'per_page',
        ]));

        return $this->paginatedResponse(OrderResource::collection($orders));
    }

    /**
     * GET /api/v1/vendor/orders/{order}
     * Show order details for a vendor.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if ($order->vendor_id !== $vendor->id) {
            return $this->errorResponse('Order not found for your shop.', null, 404);
        }

        $order->load(['items', 'statusHistories', 'user']);

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * PATCH /api/v1/vendor/orders/{order}/status
     * Update order status (vendor can move through allowed transitions).
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if ($order->vendor_id !== $vendor->id) {
            return $this->errorResponse('Order not found for your shop.', null, 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:confirmed,processing,shipped,delivered,cancelled',
            'note'   => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->orderService->updateStatus(
                $order,
                $validated['status'],
                $validated['note'] ?? null
            );

            return $this->successResponse(new OrderResource($order), 'Order status updated.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 400);
        }
    }
}
