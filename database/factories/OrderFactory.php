<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);

        return [
            'user_id'         => User::factory(),
            'event_id'        => Event::factory(),
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'total_amount'    => $subtotal,
            'status'          => 'pending',
            'payment_status'  => 'unpaid',
            'payment_method'  => null,
            'currency'        => 'BDT',
            'reserved_at'     => now(),
            'expires_at'      => now()->addMinutes(15),
        ];
    }

    public function confirmed(): static
    {
        return $this->state([
            'status'         => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'sslcommerz',
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subMinutes(5),
        ]);
    }
}
