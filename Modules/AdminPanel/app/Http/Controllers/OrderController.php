<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Orders\Models\Order;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Orders\Services\OrderService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController
{
    use ApiResponse;

    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['user', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->search, fn($q) => $q->where('order_number', 'like', "%{$request->search}%"))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['items', 'statusHistories', 'payment', 'user']);
        return $this->successResponse(new OrderResource($order));
    }

    public function updateStatus(Order $order, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'note'   => 'nullable|string|max:500',
        ]);

        $order = $this->orderService->updateStatus(
            $order,
            $validated['status'],
            $validated['note'] ?? null
        );

        return $this->successResponse(new OrderResource($order), 'Order status updated.');
    }

    public function updateAdminNote(Order $order, Request $request): JsonResponse
    {
        $validated = $request->validate(['note' => 'nullable|string|max:1000']);
        $order->update(['admin_note' => $validated['note'] ?? null]);

        return $this->successResponse($order, 'Admin note updated.');
    }
}
