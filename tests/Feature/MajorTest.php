<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MajorTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $siswaUser;

    protected Department $tikDept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            DepartmentSeeder::class,
            MajorSeeder::class,
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->siswaUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->tikDept = Department::where('code', 'TIK')->firstOrFail();
    }

    public function test_non_admin_cannot_access_majors_crud(): void
    {
        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson('/api/admin/majors')
            ->assertForbidden();
    }

    public function test_admin_can_list_majors_with_department(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/majors');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(15, 'data.data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'departmentId',
                            'department' => [
                                'id',
                                'code',
                                'name',
                            ],
                            'code',
                            'name',
                            'description',
                            'isActive',
                        ],
                    ],
                ],
            ]);
    }

    public function test_admin_can_filter_majors_by_department(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/majors?department_id={$this->tikDept->id}");

        $response->assertOk()
            ->assertJsonCount(5, 'data.data')
            ->assertJsonPath('data.data.0.department.code', 'TIK');
    }

    public function test_admin_can_fetch_major_form_options(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/majors/options');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'departments' => [
                        '*' => ['id', 'code', 'name'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_create_new_major_with_department(): void
    {
        $payload = [
            'department_id' => $this->tikDept->id,
            'code' => 'SIJA',
            'name' => 'Sistem Informatika, Jaringan, dan Aplikasi',
            'description' => 'Program SIJA 4 Tahun',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/majors', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'SIJA');
        $this->assertEquals($this->tikDept->id, decrypt($response->json('data.departmentId')));

        $this->assertDatabaseHas('majors', [
            'code' => 'SIJA',
            'department_id' => $this->tikDept->id,
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_cannot_create_major_with_invalid_department(): void
    {
        $payload = [
            'department_id' => 99999,
            'code' => 'SIJA',
            'name' => 'Sistem Informatika',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/majors', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['department_id']);
    }

    public function test_admin_can_update_major(): void
    {
        $major = Major::where('code', 'RPL')->firstOrFail();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/majors/{$major->id}", [
                'department_id' => $this->tikDept->id,
                'code' => 'PPLG',
                'name' => 'Pengembangan Perangkat Lunak dan Gim',
                'description' => 'Kurikulum Merdeka PPLG',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.code', 'PPLG')
            ->assertJsonPath('data.name', 'Pengembangan Perangkat Lunak dan Gim');

        $this->assertDatabaseHas('majors', [
            'id' => $major->id,
            'code' => 'PPLG',
            'updated_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_toggle_major_active_status(): void
    {
        $major = Major::where('code', 'RPL')->firstOrFail();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/majors/{$major->id}/toggle-active");

        $response->assertOk()
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('majors', [
            'id' => $major->id,
            'is_active' => false,
        ]);
    }

    public function test_cannot_delete_major_used_by_student(): void
    {
        $major = Major::where('code', 'RPL')->firstOrFail();

        StudentAlumni::create([
            'user_id' => $this->siswaUser->id,
            'major_id' => $major->id,
            'nis' => '12345678',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/admin/majors/{$major->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('majors', [
            'id' => $major->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_delete_unused_major(): void
    {
        $unusedMajor = Major::create([
            'department_id' => $this->tikDept->id,
            'code' => 'UNUSED',
            'name' => 'Jurusan Tidak Terpakai',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/admin/majors/{$unusedMajor->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('majors', [
            'id' => $unusedMajor->id,
            'deleted_by' => $this->adminUser->id,
        ]);
    }
}
