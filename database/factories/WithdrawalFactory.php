<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WithdrawalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'amount'     => fake()->randomFloat(2, 500, 50000),
            'status'     => 'pending',
            'note'       => null,
            'admin_note' => null,
            'settled_at' => null,
        ];
    }

    public function settled(): static
    {
        return $this->state([
            'status'     => 'settled',
            'settled_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'     => 'rejected',
            'admin_note' => fake()->sentence(),
        ]);
    }
}
