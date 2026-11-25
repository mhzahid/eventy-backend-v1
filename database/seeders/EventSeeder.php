<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\OrganizerProfile;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organizer  = User::where('email', 'organizer@eventy.test')->first();
        $techCat    = Category::where('name', 'Tech')->first();
        $musicCat   = Category::where('name', 'Music')->first();
        $foodCat    = Category::where('name', 'Food & Drink')->first();
        $sslcommerz = PaymentGateway::where('name', 'sslcommerz')->first();
        $bkash      = PaymentGateway::where('name', 'bkash')->first();

        $events = [
            [
                'name'               => 'Dhaka Music Fest 2026',
                'prefix'             => 'DMF26',
                'status'             => 'published',
                'payment_gateway_id' => $sslcommerz->id,
                'category_id'        => $musicCat->id,
                'event_date'         => now()->addDays(30),
                'start_time'         => now()->addDays(30)->setTime(10, 0),
                'sell_end_time'      => now()->addDays(29),
            ],
            [
                'name'               => 'Tech Summit Bangladesh',
                'prefix'             => 'TSB26',
                'status'             => 'published',
                'payment_gateway_id' => $bkash->id,
                'category_id'        => $techCat->id,
                'event_date'         => now()->addDays(45),
                'start_time'         => now()->addDays(45)->setTime(9, 0),
                'sell_end_time'      => now()->addDays(44),
            ],
            [
                'name'               => 'Chittagong Food Festival',
                'prefix'             => 'CFF26',
                'status'             => 'draft',
                'payment_gateway_id' => $sslcommerz->id,
                'category_id'        => $foodCat->id,
                'event_date'         => now()->addDays(60),
                'start_time'         => now()->addDays(60)->setTime(11, 0),
                'sell_end_time'      => now()->addDays(59),
            ],
        ];

        foreach ($events as $data) {
            Event::create([
                'user_id'            => $organizer->id,
                'name'               => $data['name'],
                'slug'               => Str::slug($data['name']),
                'prefix'             => $data['prefix'],
                'description'        => fake()->paragraphs(2, true),
                'location'           => fake()->address(),
                'status'             => $data['status'],
                'payment_gateway_id' => $data['payment_gateway_id'],
                'category_id'        => $data['category_id'],
                'event_date'         => $data['event_date'],
                'start_time'         => $data['start_time'],
                'sell_end_time'      => $data['sell_end_time'],
                'support_email'      => fake()->safeEmail(),
                'support_number'     => fake()->numerify('+8801#########'),
            ]);
        }
    }
}