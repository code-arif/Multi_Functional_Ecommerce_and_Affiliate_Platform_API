<?php

namespace Modules\Payments\Services;

use Illuminate\Support\Str;

class CodGateway implements PaymentGatewayInterface
{
    public function charge(array $data): array
    {
        return [
            'success'       => true,
            'transaction_id' => 'COD-' . strtoupper(Str::random(16)),
            'status'        => 'pending',
            'message'       => 'Cash on delivery order placed.',
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return [
            'success' => false,
            'message' => 'COD orders cannot be refunded through gateway.',
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'success'       => true,
            'status'        => 'pending',
            'transaction_id' => $transactionId,
        ];
    }
}
