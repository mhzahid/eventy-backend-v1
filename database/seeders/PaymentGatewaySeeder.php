<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            ['name' => 'sslcommerz', 'transaction_fee' => 2.00, 'type' => 'percentage', 'is_active' => true],
            ['name' => 'bkash',      'transaction_fee' => 1.50, 'type' => 'percentage', 'is_active' => true],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::create($gateway);
        }
    }
}
