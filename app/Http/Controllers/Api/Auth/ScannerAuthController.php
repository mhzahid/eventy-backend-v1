<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ScannerLoginRequest;
use App\Http\Resources\Auth\ScannerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScannerAuthController extends Controller
{
    public function login(ScannerLoginRequest $request): JsonResponse
    {
        $key = 'scanner-login:' . Str::lower($request->input('email')) . '|' . $request->ip();

        $maxAttempts = 5;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (!Auth::attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
            RateLimiter::increment($key);
            $attemptsLeft = RateLimiter::retriesLeft($key, $maxAttempts);
            throw ValidationException::withMessages([
                'message'      => ['The provided credentials are incorrect.'],
                'attempts_left' => ["You have $attemptsLeft attempts left."],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // if (!$user->hasRole('ticket-scanner')) {
        //     Auth::logout();
        //     RateLimiter::increment($key);
        //     throw ValidationException::withMessages([
        //         'message' => ['The provided credentials are incorrect.'],
        //     ]);
        // }

        RateLimiter::clear($key);

        $token = $user->createToken('scanner-token', ['*'], $this->tokenExpiry());

        $user->load('scannerProfile.organizer.organizerProfile');

        return response()->json([
            'data' => (new ScannerResource($user))->withToken(
                $token->plainTextToken,
                $token->accessToken->expires_at
            ),
        ]);
    }

    private function tokenExpiry(): ?\DateTimeInterface
    {
        $minutes = 1440; // 24 hours
        return $minutes ? now()->addMinutes($minutes) : null;
    }
}
