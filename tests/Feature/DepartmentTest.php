<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $siswaUser;

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
    }

    public function test_non_admin_cannot_access_departments_crud(): void
    {
        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson('/api/admin/departments')
            ->assertForbidden();
    }

    public function test_admin_can_list_departments_with_majors_count(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/departments');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'data.data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'code',
                            'name',
                            'description',
                            'isActive',
                            'majorsCount',
                        ],
                    ],
                ],
            ]);
    }

    public function test_admin_can_filter_and_search_departments(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/departments?search=TIK');

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.code', 'TIK');
    }

    public function test_admin_can_fetch_department_form_options(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/departments/options');

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

    public function test_admin_can_create_new_department(): void
    {
        $payload = [
            'code' => 'KESEHATAN',
            'name' => 'Kesehatan dan Farmasi',
            'description' => 'Program kesehatan dan farmasi klinis',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/departments', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'KESEHATAN')
            ->assertJsonPath('data.name', 'Kesehatan dan Farmasi');

        $this->assertDatabaseHas('departments', [
            'code' => 'KESEHATAN',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_cannot_create_department_with_duplicate_code(): void
    {
        $payload = [
            'code' => 'TIK',
            'name' => 'TIK Duplikat',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/departments', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_admin_can_show_department_detail_with_majors(): void
    {
        $dept = Department::where('code', 'TIK')->firstOrFail();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/departments/{$dept->id}");

        $response->assertOk()
            ->assertJsonPath('data.code', 'TIK')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'name',
                    'majors' => [
                        '*' => ['id', 'code', 'name'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_department(): void
    {
        $dept = Department::where('code', 'TIK')->firstOrFail();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/departments/{$dept->id}", [
                'code' => 'TIK_NEW',
                'name' => 'TIK Terupdate',
                'description' => 'Deskripsi baru',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.code', 'TIK_NEW')
            ->assertJsonPath('data.name', 'TIK Terupdate');

        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'code' => 'TIK_NEW',
            'updated_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_toggle_department_active_status(): void
    {
        $dept = Department::where('code', 'TIK')->firstOrFail();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/departments/{$dept->id}/toggle-active");

        $response->assertOk()
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'is_active' => false,
        ]);
    }

    public function test_cannot_delete_department_with_associated_majors(): void
    {
        $dept = Department::where('code', 'TIK')->firstOrFail();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/admin/departments/{$dept->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_delete_department_without_majors(): void
    {
        $emptyDept = Department::create([
            'code' => 'KOSONG',
            'name' => 'Departemen Kosong',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/admin/departments/{$emptyDept->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('departments', [
            'id' => $emptyDept->id,
            'deleted_by' => $this->adminUser->id,
        ]);
    }
}
