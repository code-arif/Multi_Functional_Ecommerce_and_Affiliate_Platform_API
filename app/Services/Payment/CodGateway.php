<?php

namespace App\Services\Payment;

class CodGateway implements PaymentGatewayInterface
{
    public function initiate(array $orderData): array
    {
        return [
            'success'        => true,
            'transaction_id' => 'COD-' . $orderData['order_number'],
            'message'        => 'Cash on delivery order confirmed.',
            'redirect_url'   => null,
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success' => true,
            'status'  => 'pending', // COD is verified on delivery
            'message' => 'COD payment pending delivery.',
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return [
            'success' => true,
            'message' => 'COD refund processed manually.',
        ];
    }

    public function getName(): string
    {
        return 'cod';
    }
}
