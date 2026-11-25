<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizerBalanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'total_sales'      => 0,
            'available_amount' => 0,
            'withdrawn_amount' => 0,
        ];
    }
}
