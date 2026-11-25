<?php

namespace App\Services;

use App\Models\OrganizerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrganizerAuthService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'phone'    => $data['phone'],
                'password' => $data['password'],
            ]);

            $user->assignRole('organizer');

            OrganizerProfile::create([
                'user_id'           => $user->id,
                'organization_name' => $data['organization_name'],
                'country_id'        => $data['country_id'],
            ]);

            $user->organizerBalance()->create([
                'available_amount' => 0,
            ]);

            $newToken = $user->createToken('organizer-token', ['*'], $this->tokenExpiry());

            return [
                'user'             => $user->load('organizerProfile'),
                'token'            => $newToken->plainTextToken,
                'token_expires_at' => $newToken->accessToken->expires_at,
            ];
        });
    }

    public function login(array $data): ?array
    {
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasRole('organizer')) {
            Auth::logout();
            return null;
        }

        $newToken = $user->createToken('organizer-token', ['*'], $this->tokenExpiry());

        return [
            'user'             => $user->load('organizerProfile'),
            'token'            => $newToken->plainTextToken,
            'token_expires_at' => $newToken->accessToken->expires_at,
        ];
    }

    private function tokenExpiry(): ?\DateTimeInterface
    {
        $minutes = config('sanctum.expiration');
        return $minutes ? now()->addMinutes($minutes) : null;
    }
}