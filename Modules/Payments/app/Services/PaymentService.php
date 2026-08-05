<?php

namespace Modules\Payments\Services;

use Modules\Payments\Models\Payment;
use Modules\Payments\Models\Transaction;
use Modules\Payments\Models\Refund;
use Modules\Payments\Models\PaymentMethod;
use Modules\Orders\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

    /**
     * Process a payment for an order.
     */
    public function processPayment(Order $order, string $method, array $data = []): Payment
    {
        $gateway = $this->resolveGateway($method);

        $result = $gateway->charge(array_merge($data, [
            'amount'   => $order->total,
            'currency' => config('ecommerce.currency', 'BDT'),
            'order_id' => $order->id,
        ]));

        return DB::transaction(function () use ($order, $method, $data, $result) {
            $status = $result['status'] === 'completed' ? 'completed' : 'pending';

            $payment = Payment::create([
                'order_id'        => $order->id,
                'payment_method'  => $method,
                'payment_status'  => $status,
                'transaction_id'  => $result['transaction_id'] ?? null,
                'gateway'         => $method,
                'status'          => $status,
                'amount'          => $order->total,
                'currency'        => config('ecommerce.currency', 'BDT'),
                'gateway_response' => $result,
                'paid_at'         => $status === 'completed' ? now() : null,
            ]);

            // Log transaction
            $payment->transactions()->create([
                'order_id'       => $order->id,
                'transaction_id' => $result['transaction_id'] ?? null,
                'type'           => 'payment',
                'amount'         => $order->total,
                'fee'            => $result['fee'] ?? 0,
                'net'            => $order->total - ($result['fee'] ?? 0),
                'currency'       => config('ecommerce.currency', 'BDT'),
                'status'         => $status,
                'gateway_response' => $result,
            ]);

            if ($status === 'completed') {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at'        => now(),
                ]);
            }

            Log::info('Payment processed', [
                'order_id'       => $order->id,
                'method'         => $method,
                'transaction_id' => $result['transaction_id'] ?? null,
                'status'         => $status,
            ]);

            return $payment;
        });
    }

    /**
     * Process a full or partial refund.
     */
    public function processRefund(Payment $payment, float $amount, string $reason = '', ?User $processedBy = null): Refund
    {
        $gateway = $this->resolveGateway($payment->payment_method ?? $payment->gateway);
        $result = $gateway->refund($payment->transaction_id, $amount);

        return DB::transaction(function () use ($payment, $amount, $reason, $processedBy, $result) {
            $refund = Refund::create([
                'payment_id'           => $payment->id,
                'order_id'             => $payment->order_id,
                'refund_transaction_id' => $result['refund_id'] ?? $result['transaction_id'] ?? null,
                'amount'               => $amount,
                'currency'             => $payment->currency,
                'reason'               => $reason,
                'status'               => $result['status'] === 'succeeded' ? 'completed' : ($result['success'] ? 'pending' : 'failed'),
                'processed_by'         => $processedBy?->id,
                'processed_at'         => $result['status'] === 'succeeded' ? now() : null,
                'gateway_response'     => $result,
            ]);

            // Log refund transaction
            $payment->transactions()->create([
                'order_id'       => $payment->order_id,
                'transaction_id' => $refund->refund_transaction_id,
                'type'           => $refund->amount >= $payment->amount ? 'refund' : 'partial_refund',
                'amount'         => -$amount,
                'fee'            => $result['fee'] ?? 0,
                'net'            => -($amount + ($result['fee'] ?? 0)),
                'currency'       => $payment->currency,
                'status'         => $refund->status === 'completed' ? 'completed' : 'pending',
                'gateway_response' => $result,
                'notes'          => $reason,
            ]);

            // Update payment status
            $totalRefunded = $payment->refunds()->completed()->sum('amount');
            if ($totalRefunded >= $payment->amount) {
                $payment->update(['payment_status' => 'refunded']);
                $payment->order->update(['payment_status' => 'refunded']);
            } else {
                $payment->update(['payment_status' => 'partially_refunded']);
            }

            Log::info('Refund processed', [
                'payment_id' => $payment->id,
                'order_id'   => $payment->order_id,
                'amount'     => $amount,
                'status'     => $refund->status,
            ]);

            return $refund;
        });
    }

    /**
     * Verify a payment with the gateway.
     */
    public function verifyPayment(string $transactionId, string $method): array
    {
        $gateway = $this->resolveGateway($method);
        return $gateway->verify($transactionId);
    }

    /**
     * Get payment history for an order.
     */
    public function getOrderPayments(Order $order)
    {
        return $order->payment()->with('transactions', 'refunds')->first();
    }

    // ─── Payment Method Management ────────────────────────────────

    public function getUserPaymentMethods(User $user)
    {
        return PaymentMethod::where('user_id', $user->id)->get();
    }

    public function storePaymentMethod(User $user, array $data): PaymentMethod
    {
        if (!empty($data['is_default'])) {
            PaymentMethod::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $method = PaymentMethod::create(array_merge($data, ['user_id' => $user->id]));

        // If this is the first method, make it default
        if (PaymentMethod::where('user_id', $user->id)->count() === 1) {
            $method->update(['is_default' => true]);
        }

        return $method;
    }

    public function deletePaymentMethod(PaymentMethod $method, User $user): void
    {
        if ($method->user_id !== $user->id) {
            abort(403);
        }
        $method->delete();
    }

    // ─── Gateway Registration ─────────────────────────────────────

    public function registerGateway(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function resolveGateway(string $method): PaymentGatewayInterface
    {
        return $this->gateways[$method] ?? throw new \InvalidArgumentException("Unsupported payment method: {$method}");
    }
}
