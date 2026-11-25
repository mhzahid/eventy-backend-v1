<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\AccountOtpMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private const OTP_TTL = 5;

    public function info(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    public function updateName(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $request->user()->update(['name' => $request->name]);

        return response()->json(['message' => 'Name updated successfully.']);
    }

    public function updateEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'new_email' => ['required', 'email', 'unique:users,email'],
        ]);

        if (!$request->filled('otp')) {
            return $this->sendOtp($user->email, 'email');
        }

        $request->validate(['otp' => 'digits:6']);

        if (!$this->verifyOtp("otp_email_{$user->email}", $request->otp)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $user->update(['email' => $request->new_email]);

        return response()->json(['message' => 'Email updated successfully.']);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!$request->filled('otp')) {
            return $this->sendOtp($user->email, 'password');
        }

        $request->validate(['otp' => 'digits:6']);

        if (!$this->verifyOtp("otp_password_{$user->email}", $request->otp)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $user->update(['password' => $request->new_password]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    private function sendOtp(string $email, string $purpose): JsonResponse
    {
        $otp = rand(100000, 999999);
        Cache::put("otp_{$purpose}_{$email}", $otp, now()->addMinutes(self::OTP_TTL));
        Mail::to($email)->send(new AccountOtpMail($otp, $purpose, self::OTP_TTL));

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    private function verifyOtp(string $cacheKey, string $otp): bool
    {
        $cached = Cache::get($cacheKey);

        if (!$cached || $cached != $otp) {
            return false;
        }

        Cache::forget($cacheKey);
        return true;
    }
}