<?php

namespace App\Contracts;

use App\DTOs\PaymentResult;

interface PaymentGatewayInterface
{
    public function initiate(array $payload): PaymentResult;
    public function verify(array $payload): PaymentResult;
    public function getOrderId(array $payload): ?string;
}
