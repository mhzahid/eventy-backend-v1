<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttendeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'event_id'      => Event::factory(),
            'name'          => fake()->name(),
            'email'         => fake()->safeEmail(),
            'phone'         => fake()->phoneNumber(),
            'ticket_code'   => strtoupper(Str::random(5)) . '-' . strtoupper(Str::random(8)),
            'entry_status'  => false,
            'entry_time'    => null,
        ];
    }

    public function checkedIn(): static
    {
        return $this->state([
            'entry_status' => true,
            'entry_time'   => now(),
        ]);
    }
}
