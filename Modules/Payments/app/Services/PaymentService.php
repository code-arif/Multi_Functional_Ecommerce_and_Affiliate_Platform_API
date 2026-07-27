<?php

namespace Modules\Payments\Services;

use Modules\Payments\Models\Payment;
use Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected array $gateways = [];

    public function __construct()
    {
        $this->gateways = [
            'cod'    => app(CodGateway::class),
            'stripe' => app(StripeGateway::class),
        ];
    }

    public function processPayment(Order $order, string $method, array $data = []): Payment
    {
        $gateway = $this->gateways[$method] ?? throw new \InvalidArgumentException("Unsupported payment method: {$method}");

        $result = $gateway->charge(array_merge($data, [
            'amount'   => $order->total,
            'currency' => config('ecommerce.currency', 'BDT'),
            'order_id' => $order->id,
        ]));

        $payment = Payment::create([
            'order_id'        => $order->id,
            'payment_method'  => $method,
            'payment_status'  => $result['status'],
            'transaction_id'  => $result['transaction_id'] ?? null,
            'amount'          => $order->total,
            'currency'        => config('ecommerce.currency', 'BDT'),
            'gateway_response' => $result,
            'paid_at'         => $result['status'] === 'completed' ? now() : null,
        ]);

        if ($result['status'] === 'completed') {
            $order->update([
                'payment_status' => 'paid',
                'paid_at'        => now(),
            ]);
        }

        Log::info('Payment processed', [
            'order_id'       => $order->id,
            'method'         => $method,
            'transaction_id' => $result['transaction_id'] ?? null,
            'status'         => $result['status'],
        ]);

        return $payment;
    }

    public function processRefund(Payment $payment, float $amount): array
    {
        $gateway = $this->gateways[$payment->payment_method] ?? throw new \InvalidArgumentException("Unsupported payment method: {$payment->payment_method}");
        return $gateway->refund($payment->transaction_id, $amount);
    }

    public function verifyPayment(string $transactionId, string $method): array
    {
        $gateway = $this->gateways[$method] ?? throw new \InvalidArgumentException("Unsupported payment method: {$method}");
        return $gateway->verify($transactionId);
    }

    public function registerGateway(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }
}
