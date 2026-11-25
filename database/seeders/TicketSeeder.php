<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $ticketTypes = [
            ['name' => 'General',    'price' => 500,  'quantity' => 200],
            ['name' => 'VIP',        'price' => 1500, 'quantity' => 50],
            ['name' => 'Early Bird', 'price' => 300,  'quantity' => 100],
        ];

        Event::all()->each(function (Event $event) use ($ticketTypes) {
            foreach ($ticketTypes as $type) {
                Ticket::create([
                    'event_id'          => $event->id,
                    'name'              => $type['name'],
                    'price'             => $type['price'],
                    'quantity'          => $type['quantity'],
                    'sold_quantity'     => 0,
                    'reserved_quantity' => 0,
                ]);
            }
        });
    }
}
