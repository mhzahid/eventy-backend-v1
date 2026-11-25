<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'name'          => fake()->company(),
            'business_type' => fake()->randomElement(['LLC', 'Sole Proprietorship', 'Partnership', 'NGO']),
            'address'       => fake()->address(),
            'contact'       => fake()->phoneNumber(),
            'website'       => fake()->url(),
            'logo'          => null,
        ];
    }
}
