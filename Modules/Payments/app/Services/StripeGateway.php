<?php

namespace Modules\Payments\Services;

use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Refund;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function charge(array $data): array
    {
        try {
            $charge = Charge::create([
                'amount'      => (int) ($data['amount'] * 100), // Convert to cents
                'currency'    => strtolower($data['currency'] ?? 'usd'),
                'source'      => $data['source'] ?? $data['token'] ?? null,
                'description' => "Order #{$data['order_id']}",
                'metadata'    => [
                    'order_id' => $data['order_id'],
                ],
            ]);

            return [
                'success'        => true,
                'transaction_id' => $charge->id,
                'status'         => $charge->status === 'succeeded' ? 'completed' : 'pending',
                'charge'         => $charge->toArray(),
                'message'        => 'Payment processed successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Stripe payment failed', [
                'error'   => $e->getMessage(),
                'order_id' => $data['order_id'] ?? null,
            ]);

            return [
                'success' => false,
                'status'  => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function refund(string $transactionId, float $amount): array
    {
        try {
            $refund = Refund::create([
                'charge'     => $transactionId,
                'amount'     => (int) ($amount * 100),
            ]);

            return [
                'success'        => true,
                'refund_id'      => $refund->id,
                'status'         => $refund->status,
                'message'        => 'Refund processed successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Stripe refund failed', [
                'transaction_id' => $transactionId,
                'error'          => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status'  => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verify(string $transactionId): array
    {
        try {
            $charge = Charge::retrieve($transactionId);

            return [
                'success'        => true,
                'status'         => $charge->status === 'succeeded' ? 'completed' : $charge->status,
                'transaction_id' => $charge->id,
                'amount'         => $charge->amount / 100,
                'currency'       => $charge->currency,
                'charge'         => $charge->toArray(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status'  => 'unknown',
                'message' => $e->getMessage(),
            ];
        }
    }
}
