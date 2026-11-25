<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 100, 5000);
        $quantity  = fake()->numberBetween(1, 5);

        return [
            'order_id'   => Order::factory(),
            'ticket_id'  => Ticket::factory(),
            'quantity'   => $quantity,
            'unit_price' => $unitPrice,
            'subtotal'   => $unitPrice * $quantity,
        ];
    }
}
