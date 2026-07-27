<?php

namespace Modules\Payments\Services;

interface PaymentGatewayInterface
{
    public function charge(array $data): array;
    public function refund(string $transactionId, float $amount): array;
    public function verify(string $transactionId): array;
}
