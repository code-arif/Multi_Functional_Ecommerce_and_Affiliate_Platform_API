<?php

namespace Modules\Payments\Http\Controllers;

use Modules\Payments\Services\PaymentService;
use Modules\Payments\Http\Resources\PaymentResource;
use Modules\Payments\Http\Resources\TransactionResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController
{
    use ApiResponse;

    public function __construct(private PaymentService $paymentService) {}

    /**
     * POST /api/v1/payments/process
     * Process payment for an order.
     */
    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'       => 'required|exists:orders,id',
            'payment_method' => 'required|string|in:cod,stripe',
            'token'          => 'required_if:payment_method,stripe|string',
        ]);

        $order = \Modules\Orders\Models\Order::where('user_id', $request->user()->id)
            ->findOrFail($validated['order_id']);

        if ($order->payment_status === 'paid') {
            return $this->errorResponse('Order is already paid.', null, 400);
        }

        $payment = $this->paymentService->processPayment($order, $validated['payment_method'], $request->all());

        return $this->createdResponse(new PaymentResource($payment), 'Payment initiated.');
    }

    /**
     * GET /api/v1/orders/{order}/payment
     * Get payment details for an order.
     */
    public function show(string $orderNumber, Request $request): JsonResponse
    {
        $order = \Modules\Orders\Models\Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $payment = $this->paymentService->getOrderPayments($order);

        if (!$payment) {
            return $this->successResponse(null, 'No payment found for this order.');
        }

        return $this->successResponse(new PaymentResource($payment));
    }

    /**
     * GET /api/v1/payments/methods
     * List saved payment methods for the user.
     */
    public function methods(Request $request): JsonResponse
    {
        $methods = $this->paymentService->getUserPaymentMethods($request->user());
        return $this->successResponse($methods);
    }

    /**
     * POST /api/v1/payments/methods
     * Save a payment method.
     */
    public function storeMethod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gateway'          => 'required|string|max:50',
            'gateway_method_id' => 'required|string|max:100',
            'type'             => 'nullable|string|max:50',
            'label'            => 'nullable|string|max:100',
            'details'          => 'nullable|array',
            'is_default'       => 'boolean',
        ]);

        $method = $this->paymentService->storePaymentMethod($request->user(), $validated);

        return $this->createdResponse($method, 'Payment method saved.');
    }

    /**
     * DELETE /api/v1/payments/methods/{method}
     * Delete a saved payment method.
     */
    public function destroyMethod(int $method, Request $request): JsonResponse
    {
        $methodModel = \Modules\Payments\Models\PaymentMethod::findOrFail($method);
        $this->paymentService->deletePaymentMethod($methodModel, $request->user());

        return $this->noContentResponse('Payment method removed.');
    }
}
