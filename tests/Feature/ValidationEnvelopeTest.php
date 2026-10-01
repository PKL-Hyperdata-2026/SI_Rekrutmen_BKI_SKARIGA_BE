<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_failure_uses_unified_envelope(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['email']]);
    }

    public function test_validation_failure_envelope_across_request_families(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/departments', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['code', 'name']]);
    }
}
