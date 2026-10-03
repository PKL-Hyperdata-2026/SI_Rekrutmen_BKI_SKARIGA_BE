<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const LOGIN_MAX_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 1800;

    public function attemptLogin(string $email, string $password): array
    {
        $throttleKey = $this->throttleKey($email);

        if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return [
                'success' => false,
                'message' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$minutes} menit.",
                'code' => 429,
            ];
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => ['Invalid Credentials!'],
            ]);
        }

        if (! $user->is_active) {
            return [
                'success' => false,
                'message' => 'Your account is deactivated. Contact admin for further help.',
                'code' => 403,
            ];
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'user' => $user,
            'token' => $token,
        ];
    }

    private function throttleKey(string $email): string
    {
        return 'login:'.hash('sha256', Str::lower($email));
    }
}
