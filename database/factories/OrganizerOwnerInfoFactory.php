<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizerOwnerInfoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'name'         => fake()->name(),
            'id_type'      => fake()->randomElement(['national_id', 'passport']),
            'id_number'    => fake()->numerify('##########'),
            'phone_number' => fake()->phoneNumber(),
            'mail'         => fake()->safeEmail(),
        ];
    }
}
