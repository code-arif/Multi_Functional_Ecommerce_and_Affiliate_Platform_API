<?php

namespace Modules\Orders\Http\Controllers\Customer;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Traits\ApiResponse;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Http\Resources\InvoiceResource;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Orders\Http\Resources\OrderStatusHistoryResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\InvoiceService;
use Modules\Orders\Services\OrderService;

class OrderController
{
    use ApiResponse;

    public function __construct(
        private OrderService $orderService,
        private InvoiceService $invoiceService,
    ) {
    }

    /**
     * GET /api/v1/orders — my orders.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getCustomerOrders(
            $request->user()->id,
            $request->only(['status', 'payment_status', 'from', 'to', 'per_page'])
        );

        return $this->paginatedResponse(OrderResource::collection($orders), 'Orders fetched successfully.');
    }

    /**
     * GET /api/v1/orders/summary — quick counts of my orders.
     */
    public function summary(Request $request): JsonResponse
    {
        $counts = Order::forUser($request->user()->id)
            ->select('status', DB::raw('COUNT(*) as order_count'))
            ->groupBy('status')
            ->pluck('order_count', 'status');

        return $this->successResponse([
            'total'     => $counts->sum(),
            'by_status' => $counts,
            'active'    => Order::forUser($request->user()->id)
                ->whereIn('status', OrderStatus::liveStatusValues())
                ->count(),
        ]);
    }

    /**
     * GET /api/v1/orders/{orderNumber} — my order detail.
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        try {
            $order = $this->orderService->findForCustomer($request->user()->id, $orderNumber);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No order found.', null, 404);
        }

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * GET /api/v1/orders/{orderNumber}/status-history
     */
    public function statusHistory(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return $this->errorResponse('No order found.', null, 404);
        }

        $order->load('statusHistories');

        return $this->successResponse(OrderStatusHistoryResource::collection($order->statusHistories));
    }

    /**
     * GET /api/v1/orders/{orderNumber}/invoice
     */
    public function invoice(Request $request, string $orderNumber): JsonResponse
    {
        try {
            $order = $this->orderService->findForCustomer($request->user()->id, $orderNumber);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No order found.', null, 404);
        }

        $invoice = $this->invoiceService->generateFor($order);

        return $this->successResponse(new InvoiceResource($invoice->load('order')));
    }
}
