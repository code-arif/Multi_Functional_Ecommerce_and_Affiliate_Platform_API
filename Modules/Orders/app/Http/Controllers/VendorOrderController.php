<?php

namespace Modules\Orders\Http\Controllers;

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
     * List orders for this vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $orders = $this->orderService->getVendorOrders(
            $vendor->id,
            $request->only(['status', 'search', 'per_page'])
        );

        return $this->paginatedResponse($orders);
    }

    /**
     * GET /api/v1/vendor/orders/{order}
     * Show order details.
     */
    public function show(string $orderNumber, Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $order = $this->orderService->getVendorOrderDetail($vendor->id, $orderNumber);
        return $this->successResponse(new OrderResource($order));
    }

    /**
     * PATCH /api/v1/vendor/orders/{order}/status
     * Update order status (shipped, etc.).
     */
    public function updateStatus(string $orderNumber, Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:processing,shipped,delivered',
            'note'   => 'nullable|string|max:500',
        ]);

        $order = $this->orderService->getVendorOrderDetail($vendor->id, $orderNumber);
        $order = $this->orderService->updateStatus(
            $order,
            $validated['status'],
            $validated['note'] ?? null,
            $request->user()->id
        );

        return $this->successResponse(new OrderResource($order), 'Order status updated.');
    }
}
