<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('countries')->upsert([
            ['name' => 'Bangladesh',     'short_name' => 'BD', 'dial_code' => '+880', 'currency_code' => 'BDT', 'locale' => 'en-IN', 'timezone' => 'Asia/Dhaka',     'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'India',          'short_name' => 'IN', 'dial_code' => '+91',  'currency_code' => 'INR', 'locale' => 'en-IN', 'timezone' => 'Asia/Kolkata',   'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'United States',  'short_name' => 'US', 'dial_code' => '+1',   'currency_code' => 'USD', 'locale' => 'en-US', 'timezone' => 'America/New_York','is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'United Kingdom', 'short_name' => 'GB', 'dial_code' => '+44',  'currency_code' => 'GBP', 'locale' => 'en-GB', 'timezone' => 'Europe/London',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pakistan',       'short_name' => 'PK', 'dial_code' => '+92',  'currency_code' => 'PKR', 'locale' => 'ur-PK', 'timezone' => 'Asia/Karachi',   'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ], ['name'], ['currency_code', 'locale', 'timezone', 'updated_at']);
    }
}