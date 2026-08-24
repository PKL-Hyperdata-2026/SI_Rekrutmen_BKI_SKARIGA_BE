<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_mail_and_stores_broker_token(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'siswa@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'siswa@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        Mail::assertQueued(ResetPasswordMail::class, 1);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_forgot_password_returns_success_for_unregistered_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'tidak-ada@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_forgot_password_validates_email_format(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'bukan-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'alumni@example.com',
            'password' => Hash::make('password-lama'),
        ]);

        $this->postJson('/api/forgot-password', ['email' => $user->email]);

        $token = '';

        Mail::assertQueued(ResetPasswordMail::class, function (ResetPasswordMail $mail) use (&$token): bool {
            parse_str((string) parse_url($mail->resetUrl, PHP_URL_QUERY), $query);
            $token = (string) ($query['token'] ?? '');

            return true;
        });

        $this->assertNotSame('', $token);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $user->refresh();

        $this->assertTrue(Hash::check('password-baru-123', $user->password));
        $this->assertFalse(Hash::check('password-lama', $user->password));

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_rejects_mismatched_confirmation(): void
    {
        $response = $this->postJson('/api/reset-password', [
            'token' => 'dummy-token',
            'email' => 'siswa@example.com',
            'password' => 'password-baru-123',
            'password_confirmation' => 'berbeda-123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'hrd@example.com',
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'token-palsu',
            'email' => $user->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $response->assertStatus(422);
        $this->assertFalse(Hash::check('password-baru-123', $user->fresh()->password));
    }
}
