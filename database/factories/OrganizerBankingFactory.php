<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizerBankingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'bank_name'           => fake()->randomElement(['Dutch Bangla Bank', 'BRAC Bank', 'Islami Bank', 'City Bank']),
            'account_holder_name' => fake()->name(),
            'account_number'      => fake()->numerify('####################'),
            'routing_number'      => fake()->numerify('#########'),
            'iban'                => null,
            'swift_code'          => null,
            'currency'            => 'BDT',
        ];
    }
}
