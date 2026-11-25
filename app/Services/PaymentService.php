<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Gateways\BkashGateway;
use App\Services\Gateways\SslcommerzGateway;

class PaymentService
{
    public static function for(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'sslcommerz' => new SslcommerzGateway(),
            'bkash'      => new BkashGateway(),
            default      => throw new \InvalidArgumentException("Unsupported payment gateway: {$gateway}"),
        };
    }
}