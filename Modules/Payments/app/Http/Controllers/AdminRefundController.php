<?php

namespace Modules\Payments\Http\Controllers;

use Modules\Payments\Services\PaymentService;
use Modules\Payments\Models\Payment;
use Modules\Payments\Http\Resources\RefundResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRefundController
{
    use ApiResponse;

    public function __construct(private PaymentService $paymentService) {}

    /**
     * POST /api/v1/admin/payments/{payment}/refund
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payment->amount,
            'reason' => 'nullable|string|max:500',
        ]);

        $refund = $this->paymentService->processRefund(
            $payment,
            $validated['amount'],
            $validated['reason'] ?? '',
            $request->user()
        );

        return $this->successResponse(new RefundResource($refund), 'Refund processed.');
    }

    /**
     * GET /api/v1/admin/orders/{order}/payments
     * View payment details for an order.
     */
    public function orderPayment(string $orderNumber): JsonResponse
    {
        $order = \Modules\Orders\Models\Order::where('order_number', $orderNumber)->firstOrFail();
        $payment = $this->paymentService->getOrderPayments($order);

        if (!$payment) {
            return $this->successResponse(null, 'No payment found.');
        }

        $payment->load('transactions', 'refunds');
        return $this->successResponse($payment);
    }
}
