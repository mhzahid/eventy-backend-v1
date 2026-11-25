<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name'     => $this->faker->name(),
            'title'    => $this->faker->jobTitle(),
            'image'    => null,
        ];
    }
}
