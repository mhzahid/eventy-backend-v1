<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminAuthService
{
    public function login(array $data): ?array
    {
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            Auth::logout();
            return null;
        }

        $newToken = $user->createToken('admin-token', ['*'], $this->tokenExpiry());

        return [
            'user'             => $user,
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