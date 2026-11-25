<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFormFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'fields'   => [
                ['label' => 'Full Name', 'type' => 'text', 'required' => true],
                ['label' => 'Phone',     'type' => 'tel',  'required' => false],
            ],
        ];
    }
}
