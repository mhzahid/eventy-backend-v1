<?php

namespace App\DTOs;

readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $gateway,
        public ?string $transactionId = null,
        public ?string $redirectUrl = null,
        public ?string $message = null,
        public ?array $data = null,
    ) {}
}
