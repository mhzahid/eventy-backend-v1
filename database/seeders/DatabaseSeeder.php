<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CountrySeeder::class,
            CategorySeeder::class,
            PaymentGatewaySeeder::class,
            UserSeeder::class,
            // EventSeeder::class,
            // TicketSeeder::class,
            // OrderSeeder::class,
        ]);
    }
}
