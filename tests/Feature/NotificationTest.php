<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'siswa',
        'is_active' => true,
    ]);

    $this->otherUser = User::factory()->create([
        'role' => 'siswa',
        'is_active' => true,
    ]);
});

test('user can get unread notifications', function () {
    Notification::create([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user->id,
        'type' => 'job_vacancy',
        'title' => 'Lowongan Baru',
        'message' => 'PT Maju membuka lowongan baru.',
        'data' => ['slug' => 'lowongan-1'],
    ]);

    Notification::create([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user->id,
        'type' => 'general',
        'title' => 'Notifikasi Lama',
        'message' => 'Pesan lama.',
        'read_at' => now(),
    ]);

    Notification::create([
        'id' => (string) Str::uuid(),
        'user_id' => $this->otherUser->id,
        'type' => 'job_vacancy',
        'title' => 'Lowongan User Lain',
        'message' => 'Pesan user lain.',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/notification/unread');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('total_unread', 1)
        ->assertJsonCount(1, 'data');
});

test('user can mark notification as read', function () {
    $notification = Notification::create([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user->id,
        'type' => 'job_vacancy',
        'title' => 'Lowongan Baru',
        'message' => 'PT Maju membuka lowongan baru.',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->patchJson("/api/notification/{$notification->id}/read");

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertNotNull($notification->fresh()->read_at);
});

test('user can mark all notifications as read', function () {
    Notification::create([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user->id,
        'type' => 'job_vacancy',
        'title' => 'Lowongan 1',
        'message' => 'Pesan 1',
    ]);

    Notification::create([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user->id,
        'type' => 'job_vacancy',
        'title' => 'Lowongan 2',
        'message' => 'Pesan 2',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->patchJson('/api/notification/read-all');

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertEquals(
        0,
        Notification::where('user_id', $this->user->id)->whereNull('read_at')->count()
    );
});

test('user broadcast channel authorization rule', function () {
    $callback = Broadcast::getChannels()['user.{id}'];

    $this->assertTrue($callback($this->user, $this->user->id));
    $this->assertTrue($callback($this->user, (string) $this->user->id));
    $this->assertTrue($callback($this->user, encrypt((string) $this->user->id)));

    $this->assertFalse($callback($this->user, $this->otherUser->id));
    $this->assertFalse($callback($this->user, encrypt((string) $this->otherUser->id)));
});
