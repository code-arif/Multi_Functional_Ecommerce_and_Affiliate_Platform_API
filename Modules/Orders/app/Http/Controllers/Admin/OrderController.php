<?php

namespace Modules\Orders\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\ActivityLog;
use Modules\Core\Traits\ApiResponse;
use Modules\Orders\Exceptions\InvalidOrderTransitionException;
use Modules\Orders\Http\Requests\UpdateAdminNoteRequest;
use Modules\Orders\Http\Requests\UpdateOrderStatusRequest;
use Modules\Orders\Http\Resources\InvoiceResource;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\InvoiceService;
use Modules\Orders\Services\OrderService;
use Modules\Orders\Services\OrderStatsService;

class OrderController
{
    use ApiResponse;

    public function __construct(
        private OrderService $orderService,
        private InvoiceService $invoiceService,
        private OrderStatsService $statsService,
    ) {
    }

    /**
     * GET /api/v1/admin/orders — full order management list with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getAdminOrders(
            $request->only(['status', 'payment_status', 'vendor_id', 'search', 'from', 'to', 'per_page'])
        );

        return $this->paginatedResponse(OrderResource::collection($orders), 'Orders fetched successfully.');
    }

    /**
     * GET /api/v1/admin/orders/stats — platform order analytics.
     */
    public function stats(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->statsService->overview($request->integer('vendor_id') ?: null),
            'Order statistics.'
        );
    }

    /**
     * GET /api/v1/admin/orders/audit — complete order audit trail (Super Admin monitoring).
     */
    public function audit(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user')
            ->byModule('orders')
            ->when($request->action, fn ($q, $a) => $q->byAction($a))
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse($logs, 'Order audit trail fetched.');
    }

    /**
     * GET /api/v1/admin/orders/{order}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $order->load(['user', 'vendor', 'items', 'payment', 'statusHistories', 'cancelRequests', 'invoice', 'groupOrders']);

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * PATCH /api/v1/admin/orders/{order}/status
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->updateStatus(
                $order,
                $request->validated('status'),
                $request->validated('note'),
                $request->user(),
                'admin',
                $request->boolean('notify_customer', true)
            );

            if ($request->filled('tracking_number')) {
                $order = $this->orderService->updateTracking(
                    $order,
                    $request->input('tracking_number'),
                    $request->input('shipping_carrier')
                );
            }

            return $this->successResponse(new OrderResource($order), 'Order status updated.');
        } catch (InvalidOrderTransitionException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * PATCH /api/v1/admin/orders/{order}/note
     */
    public function updateAdminNote(UpdateAdminNoteRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->updateAdminNote($order, $request->validated('note'), $request->user());

        return $this->successResponse(new OrderResource($order), 'Admin note updated.');
    }

    /**
     * POST /api/v1/admin/orders/{order}/refund
     */
    public function refund(Request $request, Order $order): JsonResponse
    {
        $request->validate(['note' => 'nullable|string|max:1000']);

        try {
            $order = $this->orderService->refundOrder($order, $request->input('note'), $request->user());

            return $this->successResponse(new OrderResource($order), 'Order marked as refunded.');
        } catch (InvalidOrderTransitionException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * GET /api/v1/admin/orders/{order}/invoice
     */
    public function invoice(Request $request, Order $order): JsonResponse
    {
        $invoice = $this->invoiceService->generateFor($order);

        return $this->successResponse(new InvoiceResource($invoice->load('order')));
    }
}
