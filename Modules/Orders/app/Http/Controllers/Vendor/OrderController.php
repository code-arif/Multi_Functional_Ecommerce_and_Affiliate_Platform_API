<?php

namespace Modules\Orders\Http\Controllers\Vendor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\Orders\Exceptions\InvalidOrderTransitionException;
use Modules\Orders\Http\Requests\VendorTrackingRequest;
use Modules\Orders\Http\Requests\VendorUpdateOrderStatusRequest;
use Modules\Orders\Http\Resources\InvoiceResource;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\InvoiceService;
use Modules\Orders\Services\OrderService;
use Modules\Orders\Services\OrderStatsService;
use Modules\Vendor\Models\Vendor;

class OrderController
{
    use ApiResponse;

    public function __construct(
        private OrderService $orderService,
        private InvoiceService $invoiceService,
        private OrderStatsService $statsService,
    ) {
    }

    private function vendor(Request $request): Vendor
    {
        return $request->user()->vendor;
    }

    private function assertOwns(Request $request, Order $order): bool
    {
        return $order->vendor_id === $this->vendor($request)->id;
    }

    /**
     * GET /api/v1/vendor/orders — orders belonging to my shop.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getVendorOrders(
            $this->vendor($request)->id,
            $request->only(['status', 'search', 'from', 'to', 'per_page'])
        );

        return $this->paginatedResponse(OrderResource::collection($orders), 'Shop orders fetched successfully.');
    }

    /**
     * GET /api/v1/vendor/orders/stats — my shop's order statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->statsService->overview($this->vendor($request)->id),
            'Shop order statistics.'
        );
    }

    /**
     * GET /api/v1/vendor/orders/{order}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        if (!$this->assertOwns($request, $order)) {
            return $this->errorResponse('Order not found for your shop.', null, 404);
        }

        $order->load(['items', 'statusHistories', 'payment', 'cancelRequests', 'invoice', 'user', 'vendor']);

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * PATCH /api/v1/vendor/orders/{order}/status
     */
    public function updateStatus(VendorUpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        if (!$this->assertOwns($request, $order)) {
            return $this->errorResponse('Order not found for your shop.', null, 404);
        }

        try {
            $order = $this->orderService->updateStatus(
                $order,
                $request->validated('status'),
                $request->validated('note'),
                $request->user(),
                'vendor'
            );

            return $this->successResponse(new OrderResource($order), 'Order status updated.');
        } catch (InvalidOrderTransitionException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * PATCH /api/v1/vendor/orders/{order}/tracking
     */
    public function updateTracking(VendorTrackingRequest $request, Order $order): JsonResponse
    {
        if (!$this->assertOwns($request, $order)) {
            return $this->errorResponse('Order not found for your shop.', null, 404);
        }

        $order = $this->orderService->updateTracking(
            $order,
            $request->validated('tracking_number'),
            $request->validated('shipping_carrier')
        );

        return $this->successResponse(new OrderResource($order), 'Tracking information updated.');
    }

    /**
     * GET /api/v1/vendor/orders/{order}/invoice
     */
    public function invoice(Request $request, Order $order): JsonResponse
    {
        if (!$this->assertOwns($request, $order)) {
            return $this->errorResponse('Order not found for your shop.', null, 404);
        }

        $invoice = $this->invoiceService->generateFor($order);

        return $this->successResponse(new InvoiceResource($invoice));
    }
}
