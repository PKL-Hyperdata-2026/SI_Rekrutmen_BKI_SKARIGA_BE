<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('validation failure uses unified envelope', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['email']]);
});

test('validation failure envelope across request families', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $response = $this->actingAs($admin)
        ->postJson('/api/admin/departments', []);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['code', 'name']]);
});
