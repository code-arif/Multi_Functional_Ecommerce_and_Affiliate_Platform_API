<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    public function initiate(array $orderData): array;
    public function verify(string $transactionId): array;
    public function refund(string $transactionId, float $amount): array;
    public function getName(): string;
}
