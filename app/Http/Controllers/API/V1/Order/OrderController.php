<?php

namespace App\Http\Controllers\API\V1\Order;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private OrderService $orderService) {}

    /**
     * GET /api/v1/orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::forUser($request->user()->id)
            ->with(['items', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->paginatedResponse(
            OrderResource::collection($orders),
            'Orders fetched successfully.'
        );
    }

    /**
     * GET /api/v1/orders/{orderNumber}
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $userId = Auth::id(); // logged in user ID
        // dd($userId);

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $userId)
            ->with(['items', 'payment', 'statusHistories'])
            ->first(); // find() na, first() use korte hobe

        if (!$order) {
            return $this->errorResponse('No order found');
        }

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * GET /api/v1/orders/track/{token} — Guest order tracking
     */
    public function trackGuest(string $token): JsonResponse
    {
        $order = Order::where('guest_token', $token)
            ->with(['items', 'payment', 'statusHistories'])
            ->firstOrFail();

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * POST /api/v1/orders/{orderNumber}/cancel
     */
    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $order = $this->orderService->cancelOrder($order, $request->user()->id);

        return $this->successResponse(new OrderResource($order), 'Order cancelled successfully.');
    }
}
