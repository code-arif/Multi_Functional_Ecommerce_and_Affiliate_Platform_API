<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\CodGateway;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private array $gateways = [];

    public function __construct()
    {
        // Register gateways — add new ones here without touching other code
        $this->gateways['cod']        = new CodGateway();
        // $this->gateways['bkash']   = new BkashGateway();
        // $this->gateways['nagad']   = new NagadGateway();
        // $this->gateways['sslcommerz'] = new SslCommerzGateway();
    }

    public function getGateway(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new \Exception("Payment gateway '{$name}' is not supported.");
        }

        return $this->gateways[$name];
    }

    public function initiatePayment(Order $order): array
    {
        $gateway = $this->getGateway($order->payment_method);
        $result  = $gateway->initiate([
            'order_number' => $order->order_number,
            'amount'       => $order->total_amount,
            'currency'     => 'BDT',
            'customer'     => [
                'name'  => $order->shipping_name,
                'email' => $order->shipping_email,
                'phone' => $order->shipping_phone,
            ],
        ]);

        // Update payment record with transaction ID
        if ($result['success'] && isset($result['transaction_id'])) {
            $order->payment()->update([
                'transaction_id' => $result['transaction_id'],
            ]);
        }

        Log::channel('payments')->info('Payment initiated', [
            'order_id' => $order->id,
            'gateway'  => $order->payment_method,
            'amount'   => $order->total_amount,
        ]);

        return $result;
    }

    public function verifyPayment(Order $order, string $transactionId): bool
    {
        $gateway = $this->getGateway($order->payment_method);
        $result  = $gateway->verify($transactionId);

        if ($result['success']) {
            $order->payment()->update([
                'status'    => 'completed',
                'paid_at'   => now(),
                'gateway_response' => $result,
            ]);

            $order->update(['payment_status' => 'paid']);

            Log::channel('payments')->info('Payment verified', [
                'order_id'       => $order->id,
                'transaction_id' => $transactionId,
            ]);

            return true;
        }

        return false;
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
