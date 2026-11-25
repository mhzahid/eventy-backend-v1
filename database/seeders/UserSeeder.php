<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\OrganizerProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@eventy.test',
            'password' => bcrypt('123456789'),
        ]);
        $admin->assignRole('admin');

        $organizer = User::create([
            'name'     => 'Organizer',
            'email'    => 'organizer@eventy.test',
            'phone'    => '01700000001',
            'password' => bcrypt('123456789'),
        ]);
        $organizer->assignRole('organizer');

        OrganizerProfile::create([
            'user_id'           => $organizer->id,
            'organization_name' => 'Eventy Organizer',
            'country_id'        => Country::where('short_name', 'BD')->value('id'),
        ]);

        $user = User::create([
            'name'  => 'User',
            'email' => 'user@eventy.test',
            'phone' => '01700000003',
        ]);
        $user->assignRole('user');
    }
}