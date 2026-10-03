<?php

declare(strict_types=1);

use App\Models\Department;
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
});

test('non admin cannot access departments crud', function () {
    $this->actingAs($this->siswaUser, 'sanctum')
        ->getJson('/api/admin/departments')
        ->assertForbidden();
});

test('admin can list departments with majors count', function () {
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
});

test('admin can filter and search departments', function () {
    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->getJson('/api/admin/departments?search=TIK');

    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.code', 'TIK');
});

test('admin can fetch department form options', function () {
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
});

test('admin can create new department', function () {
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
});

test('cannot create department with duplicate code', function () {
    $payload = [
        'code' => 'TIK',
        'name' => 'TIK Duplikat',
    ];

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->postJson('/api/admin/departments', $payload);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

test('admin can show department detail with majors', function () {
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
});

test('admin can update department', function () {
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
});

test('admin can toggle department active status', function () {
    $dept = Department::where('code', 'TIK')->firstOrFail();

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->patchJson("/api/admin/departments/{$dept->id}/toggle-active");

    $response->assertOk()
        ->assertJsonPath('data.isActive', false);

    $this->assertDatabaseHas('departments', [
        'id' => $dept->id,
        'is_active' => false,
    ]);
});

test('cannot delete department with associated majors', function () {
    $dept = Department::where('code', 'TIK')->firstOrFail();

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->deleteJson("/api/admin/departments/{$dept->id}");

    $response->assertStatus(422)
        ->assertJsonPath('success', false);

    $this->assertDatabaseHas('departments', [
        'id' => $dept->id,
        'deleted_at' => null,
    ]);
});

test('admin can delete department without majors', function () {
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
});
