<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->hrd = User::factory()->create([
        'role' => 'hrd',
        'is_active' => true,
    ]);
});

test('superadmin can access user list', function () {
    User::factory()->count(5)->create(['role' => 'admin']);

    $response = $this->actingAs($this->superadmin)
        ->getJson('/api/admin/users');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => ['id', 'fullName', 'email', 'phone', 'role', 'isActive'],
                ],
            ],
        ]);

    $ids = collect($response->json('data.data'))->pluck('id')->map(fn ($id) => decrypt($id))->all();
    $this->assertNotContains($this->superadmin->id, $ids);
});

test('regular admin cannot access user list and gets 403', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/users');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Anda tidak memiliki hak untuk mengakses ini!',
        ]);
});

test('superadmin can access admin routes via role inheritance', function () {
    $response = $this->actingAs($this->superadmin)
        ->getJson('/api/admin/students');

    $response->assertOk();
});

test('superadmin can get user form options', function () {
    Company::factory()->create(['name' => 'PT Mitra Sejahtera', 'is_active' => true]);

    $response = $this->actingAs($this->superadmin)
        ->getJson('/api/admin/users/options');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'roles',
                'companies',
            ],
        ]);
});

test('superadmin can create a new admin user', function () {
    $payload = [
        'full_name' => 'Admin Baru',
        'email' => 'adminbaru@example.com',
        'phone' => '081234567891',
        'password' => 'secret123',
        'role' => 'admin',
        'is_active' => true,
    ];

    $response = $this->actingAs($this->superadmin)
        ->postJson('/api/admin/users', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.email', 'adminbaru@example.com')
        ->assertJsonPath('data.role', 'admin');

    $this->assertDatabaseHas('users', [
        'email' => 'adminbaru@example.com',
        'role' => 'admin',
    ]);
});

test('superadmin can create hrd user linked to company', function () {
    $company = Company::factory()->create(['is_active' => true]);

    $payload = [
        'full_name' => 'HRD PT Sukses',
        'email' => 'hrdsukses@example.com',
        'phone' => '081234567892',
        'password' => 'secret123',
        'role' => 'hrd',
        'company_id' => $company->id,
    ];

    $response = $this->actingAs($this->superadmin)
        ->postJson('/api/admin/users', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.email', 'hrdsukses@example.com')
        ->assertJsonPath('data.role', 'hrd');

    $this->assertEquals($company->id, decrypt($response->json('data.company.id')));

    $user = User::where('email', 'hrdsukses@example.com')->first();
    $this->assertEquals($user->id, $company->fresh()->user_id);
});

test('superadmin can update user and toggle active status', function () {
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $updateResponse = $this->actingAs($this->superadmin)
        ->putJson("/api/admin/users/{$user->id}", [
            'full_name' => 'Nama Diubah',
            'role' => 'admin',
        ]);

    $updateResponse->assertOk()
        ->assertJsonPath('data.fullName', 'Nama Diubah');

    $toggleResponse = $this->actingAs($this->superadmin)
        ->patchJson("/api/admin/users/{$user->id}/toggle-active");

    $toggleResponse->assertOk()
        ->assertJsonPath('data.isActive', false);
});

test('superadmin can reset user password', function () {
    $user = User::factory()->create(['role' => 'admin', 'password' => 'oldpassword']);

    $response = $this->actingAs($this->superadmin)
        ->postJson("/api/admin/users/{$user->id}/reset-password", [
            'password' => 'newpassword123',
        ]);

    $response->assertOk()
        ->assertJson(['success' => true]);
});

test('superadmin can delete user', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($this->superadmin)
        ->deleteJson("/api/admin/users/{$user->id}");

    $response->assertOk();
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});
