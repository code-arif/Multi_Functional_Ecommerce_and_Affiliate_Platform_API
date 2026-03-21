<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['user', 'items', 'payment'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->search, fn($q) =>
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhere('shipping_name', 'like', "%{$request->search}%")
                  ->orWhere('shipping_phone', 'like', "%{$request->search}%"))
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['user', 'items', 'payment', 'statusHistories.updatedBy', 'coupon']);
        return $this->successResponse(new OrderResource($order));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status'          => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'comment'         => 'nullable|string|max:500',
            'notify_customer' => 'boolean',
            'tracking_number' => 'nullable|string',
            'shipping_carrier'=> 'nullable|string',
        ]);

        if ($request->tracking_number) {
            $this->orderService->updateTracking($order, $request->tracking_number, $request->shipping_carrier ?? '');
        }

        $order = $this->orderService->updateStatus(
            $order,
            $request->status,
            $request->comment,
            $request->notify_customer ?? true,
            $request->user()->id
        );

        return $this->successResponse(new OrderResource($order), 'Order status updated.');
    }

    public function updateAdminNote(Request $request, Order $order): JsonResponse
    {
        $request->validate(['admin_note' => 'nullable|string|max:1000']);
        $order->update(['admin_note' => $request->admin_note]);
        return $this->successResponse(null, 'Note updated.');
    }
}
