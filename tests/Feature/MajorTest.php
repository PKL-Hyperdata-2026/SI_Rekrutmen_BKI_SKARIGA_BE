<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Major;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

test('non admin cannot access majors crud', function () {
    $this->actingAs($this->siswaUser, 'sanctum')
        ->getJson('/api/admin/majors')
        ->assertForbidden();
});

test('admin can list majors with department', function () {
    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->getJson('/api/admin/majors');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(15, 'data.data')
        ->assertJsonPath('data.meta.active_count', 15)
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
});

test('admin can get accurate active count across pagination', function () {
    Major::create([
        'department_id' => $this->tikDept->id,
        'code' => 'INACT',
        'name' => 'Jurusan Nonaktif',
        'is_active' => false,
    ]);

    $page1 = $this->actingAs($this->adminUser, 'sanctum')
        ->getJson('/api/admin/majors?per_page=10&page=1');

    $page1->assertOk()
        ->assertJsonCount(10, 'data.data')
        ->assertJsonPath('data.meta.total', 16)
        ->assertJsonPath('data.meta.active_count', 15);

    $page2 = $this->actingAs($this->adminUser, 'sanctum')
        ->getJson('/api/admin/majors?per_page=10&page=2');

    $page2->assertOk()
        ->assertJsonCount(6, 'data.data')
        ->assertJsonPath('data.meta.total', 16)
        ->assertJsonPath('data.meta.active_count', 15);
});

test('admin can filter majors by department', function () {
    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->getJson("/api/admin/majors?department_id={$this->tikDept->id}");

    $response->assertOk()
        ->assertJsonCount(5, 'data.data')
        ->assertJsonPath('data.data.0.department.code', 'TIK');
});

test('admin can fetch major form options', function () {
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
});

test('admin can create new major with department', function () {
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
});

test('cannot create major with invalid department', function () {
    $payload = [
        'department_id' => 99999,
        'code' => 'SIJA',
        'name' => 'Sistem Informatika',
    ];

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->postJson('/api/admin/majors', $payload);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['department_id']);
});

test('admin can update major', function () {
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
});

test('admin can toggle major active status', function () {
    $major = Major::where('code', 'RPL')->firstOrFail();

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->patchJson("/api/admin/majors/{$major->id}/toggle-active");

    $response->assertOk()
        ->assertJsonPath('data.isActive', false);

    $this->assertDatabaseHas('majors', [
        'id' => $major->id,
        'is_active' => false,
    ]);
});

test('cannot delete major used by student', function () {
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
});

test('admin can delete unused major', function () {
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
});
