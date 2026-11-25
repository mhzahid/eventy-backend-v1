<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AdminResource;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AdminAuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $key = 'admin-login:' . Str::lower($request->input('email')) . '|' . $request->ip();

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
            'data' => (new AdminResource($result['user']))->withToken($result['token'], $result['token_expires_at']),
        ]);
    }
}
