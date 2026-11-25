<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\OrganizerRegisterRequest;
use App\Http\Resources\Auth\OrganizerResource;
use App\Services\OrganizerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizerAuthController extends Controller
{
    public function __construct(private readonly OrganizerAuthService $authService) {}

    public function register(OrganizerRegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'data' => (new OrganizerResource($result['user']))->withToken($result['token'], $result['token_expires_at']),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $key = 'organizer-login:' . Str::lower($request->input('email')) . '|' . $request->ip();

        $maxAttempts = 3; // max login attempts

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $result = $this->authService->login($request->validated());

        if (!$result) {
            RateLimiter::increment($key);

            $attemptsLeft = RateLimiter::retriesLeft($key, $maxAttempts);

            throw ValidationException::withMessages([
                'message' => ['The provided credentials are incorrect.'],
                'attempts_left' => ["You have $attemptsLeft attempts left."],
            ]);
        }

        RateLimiter::clear($key);

        return response()->json([
            'data' => (new OrganizerResource($result['user']))->withToken($result['token'], $result['token_expires_at']),
        ]);
    }
}