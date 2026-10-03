<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login returns token for active user', function () {
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
});

test('login failure omits success true on 4xx', function () {
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
});
