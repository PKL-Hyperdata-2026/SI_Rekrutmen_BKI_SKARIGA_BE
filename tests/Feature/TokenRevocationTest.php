<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->target = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_password_reset_revokes_target_tokens(): void
    {
        $this->target->createToken('auth_token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->actingAs($this->superadmin)
            ->postJson("/api/admin/users/{$this->target->id}/reset-password", [
                'password' => 'NewSecret123',
            ])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_toggle_active_revokes_target_tokens(): void
    {
        $this->target->createToken('auth_token');

        $this->actingAs($this->superadmin)
            ->patchJson("/api/admin/users/{$this->target->id}/toggle-active")
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertFalse($this->target->fresh()->is_active);
    }

    public function test_role_change_revokes_target_tokens(): void
    {
        $this->target->createToken('auth_token');

        $this->actingAs($this->superadmin)
            ->putJson("/api/admin/users/{$this->target->id}", [
                'role' => 'hrd',
            ])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_superadmin_reset_password_revokes_own_tokens(): void
    {
        $this->target->createToken('auth_token');

        $this->actingAs($this->superadmin)
            ->postJson("/api/admin/users/{$this->target->id}/reset-password", [
                'password' => 'AnotherSecret123',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_user_is_rejected_on_all_authenticated_routes(): void
    {
        $this->target->update(['is_active' => false]);

        $this->actingAs($this->target, 'sanctum')
            ->getJson('/api/me')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan!',
            ]);

        $this->actingAs($this->target, 'sanctum')
            ->getJson('/api/notification')
            ->assertStatus(403);

        $this->actingAs($this->target, 'sanctum')
            ->getJson('/api/admin/companies')
            ->assertStatus(403);
    }

    public function test_active_user_still_reaches_role_routes(): void
    {
        $this->actingAs($this->target, 'sanctum')
            ->getJson('/api/admin/companies')
            ->assertOk();
    }

    public function test_deleting_alumni_revokes_linked_user_tokens(): void
    {
        $alumniUser = User::factory()->create([
            'role' => 'alumni',
            'is_active' => true,
        ]);
        $alumniUser->createToken('auth_token');

        $alumni = StudentAlumni::factory()->create([
            'user_id' => $alumniUser->id,
        ]);

        $this->actingAs($this->superadmin)
            ->deleteJson("/api/admin/alumni/{$alumni->id}")
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revokes_calling_token(): void
    {
        $token = $this->target->createToken('auth_token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_company_assignment_preserved_on_non_role_update(): void
    {
        $company = Company::factory()->create(['user_id' => $this->target->id]);
        $this->target->createToken('auth_token');

        $this->actingAs($this->superadmin)
            ->putJson("/api/admin/users/{$this->target->id}", [
                'full_name' => 'Renamed User',
            ])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'user_id' => $this->target->id]);
    }

    public function test_user_update_password_revokes_target_tokens(): void
    {
        $this->target->createToken('auth_token');

        $this->actingAs($this->superadmin)
            ->putJson("/api/admin/users/{$this->target->id}", [
                'password' => 'RotatedSecret123',
            ])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_student_update_revokes_tokens_on_deactivation(): void
    {
        $this->seed([MajorSeeder::class, ClassSeeder::class]);

        $studentUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        $studentUser->createToken('auth_token');
        $student = StudentAlumni::factory()->create(['user_id' => $studentUser->id]);

        $this->actingAs($this->superadmin)
            ->putJson("/api/admin/students/{$student->id}", array_merge(
                $this->studentPayload($student, $studentUser),
                ['is_active' => false]
            ))
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_student_update_revokes_tokens_on_password_change(): void
    {
        $this->seed([MajorSeeder::class, ClassSeeder::class]);

        $studentUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        $studentUser->createToken('auth_token');
        $student = StudentAlumni::factory()->create(['user_id' => $studentUser->id]);

        $this->actingAs($this->superadmin)
            ->putJson("/api/admin/students/{$student->id}", array_merge(
                $this->studentPayload($student, $studentUser),
                ['password' => 'BrandNewSecret123']
            ))
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function studentPayload(StudentAlumni $student, User $user): array
    {
        return [
            'nis' => $student->nis,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'class_id' => StandardType::byCategory('class')->value('id'),
            'major_id' => $student->major_id,
        ];
    }

    public function test_alumni_graduation_revokes_tokens_on_role_transition(): void
    {
        $studentUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        $studentUser->createToken('auth_token');
        $student = StudentAlumni::factory()->create([
            'user_id' => $studentUser->id,
            'graduation_year' => null,
        ]);

        $this->actingAs($this->superadmin)
            ->putJson("/api/admin/alumni/{$student->id}", [
                'graduation_year' => 2025,
            ])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
