<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_active_user(): void
    {
        $user = User::factory()->create([
            'email' => 'aktif@example.com',
            'password' => 'password-123',
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'access_token', 'user']);
    }

    public function test_login_failure_omits_success_true_on_4xx(): void
    {
        $user = User::factory()->create([
            'email' => 'nonaktif@example.com',
            'password' => 'password-123',
            'role' => 'siswa',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password-123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }
}
