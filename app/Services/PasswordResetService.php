<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;

class PasswordResetService
{
    public function sendResetLink(string $email): Responsable
    {
        $outcome = (new Timebox)->call(function () use ($email): string {
            $user = $this->getUser($email);

            if ($user === null) {
                return 'not-found';
            }

            if (Password::broker()->getRepository()->recentlyCreatedToken($user)) {
                return 'throttled';
            }

            $token = Password::createToken($user);

            Mail::to($user->email)->send(new ResetPasswordMail(
                $user->email,
                $token,
                (int) config('auth.passwords.users.expire', 60)
            ));

            return 'sent';
        }, 500_000);

        if ($outcome === 'throttled') {
            return ResponseService::make()
                ->success(false)
                ->message('Terlalu banyak permintaan reset. Silakan tunggu satu menit sebelum mencoba lagi.')
                ->code(429);
        }

        return $this->successLinkResponse();
    }

    public function reset(array $credentials): Responsable
    {
        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $this->updatePassword($user, $password);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ResponseService::make()
                ->message('Password berhasil direset. Silakan login dengan password baru Anda.');
        }

        return ResponseService::make()
            ->success(false)
            ->message('Token tidak valid atau telah kedaluwarsa. Silakan ajukan ulang tautan reset password.')
            ->code(422);
    }

    protected function getUser(string $email): ?CanResetPassword
    {
        return Password::getUser(['email' => $email]);
    }

    protected function successLinkResponse(): Responsable
    {
        return ResponseService::make()
            ->message('Tautan reset password telah dikirim ke email Anda. Silakan periksa kotak masuk atau folder spam.');
    }

    protected function updatePassword(User $user, string $password): void
    {
        $user->tokens()->delete();

        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();
    }
}
