<?php

namespace App\Contracts;

interface SnsCapable
{
    public function confirmSnsSubscription(array $payload): ?string;
}
