<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'          => Event::factory(),
            'name'              => fake()->randomElement(['General', 'VIP', 'Early Bird']),
            'price'             => fake()->randomFloat(2, 100, 5000),
            'quantity'          => 100,
            'sold_quantity'     => 0,
            'reserved_quantity' => 0,
            'sale_start'        => null,
            'sale_end'          => null,
        ];
    }

    public function soldOut(): static
    {
        return $this->state(fn(array $attr) => [
            'sold_quantity' => $attr['quantity'],
        ]);
    }

    public function withSaleWindow(): static
    {
        return $this->state([
            'sale_start' => now()->subDay(),
            'sale_end'   => now()->addDays(7),
        ]);
    }
}
