<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->sentence(3, false);

        return [
            'user_id'          => User::factory(),
            'name'             => $name,
            'slug'             => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'prefix'           => strtoupper(fake()->unique()->lexify('?????')),
            'description'      => fake()->paragraph(),
            'location'         => fake()->address(),
            'status'             => 'published',
            'event_date'         => now()->addDays(30),
            'start_time'         => now()->addDays(30)->setTime(10, 0),
            'sell_end_time'      => now()->addDays(29)->setTime(23, 59),
            'payment_gateway_id' => PaymentGateway::value('id'),
            'meta_data'          => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }
}
