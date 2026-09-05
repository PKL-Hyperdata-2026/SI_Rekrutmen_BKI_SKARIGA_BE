<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $admin;
    protected User $hrd;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_superadmin_can_access_user_list(): void
    {
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
    }

    public function test_regular_admin_cannot_access_user_list_and_gets_403(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki hak untuk mengakses ini!',
            ]);
    }

    public function test_superadmin_can_access_admin_routes_via_role_inheritance(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->getJson('/api/admin/students');

        $response->assertOk();
    }

    public function test_superadmin_can_get_user_form_options(): void
    {
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
    }

    public function test_superadmin_can_create_a_new_admin_user(): void
    {
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
    }

    public function test_superadmin_can_create_hrd_user_linked_to_company(): void
    {
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
    }

    public function test_superadmin_can_update_user_and_toggle_active_status(): void
    {
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
    }

    public function test_superadmin_can_reset_user_password(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'password' => 'oldpassword']);

        $response = $this->actingAs($this->superadmin)
            ->postJson("/api/admin/users/{$user->id}/reset-password", [
                'password' => 'newpassword123',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_superadmin_can_delete_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($this->superadmin)
            ->deleteJson("/api/admin/users/{$user->id}");

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
