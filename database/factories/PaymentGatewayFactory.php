<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentGatewayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'            => $this->faker->unique()->randomElement(['sslcommerz', 'bkash', 'nagad', 'rocket']),
            'transaction_fee' => $this->faker->randomFloat(2, 1, 3),
            'type'            => 'percentage',
        ];
    }
}
