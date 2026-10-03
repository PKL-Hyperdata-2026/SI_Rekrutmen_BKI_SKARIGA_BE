<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Department;
use App\Models\Major;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\CompanyIndustrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CompanyIndustrySeeder::class);
    $this->seed(ClassSeeder::class);

    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->hrdUser = User::factory()->create([
        'role' => 'hrd',
        'is_active' => true,
    ]);
});

test('companies select returns paginated value label shape', function () {
    Company::factory()->count(25)->create(['is_active' => true]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/companies?for_select=1&per_page=20');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.current_page', 1)
        ->assertJsonPath('data.meta.per_page', 20)
        ->assertJsonPath('data.meta.total', 25)
        ->assertJsonCount(20, 'data.data')
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    ['value', 'label', 'extra'],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ],
        ]);
});

test('companies select search filters by name', function () {
    Company::factory()->create(['name' => 'PT Maju Jaya Abadi', 'is_active' => true]);
    Company::factory()->create(['name' => 'PT Mundur Teratur', 'is_active' => true]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/companies?for_select=1&search=Maju Jaya');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.label', 'PT Maju Jaya Abadi');
});

test('companies index without for select keeps full resource', function () {
    Company::factory()->create(['name' => 'PT Tetap Sama', 'is_active' => true]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/companies?per_page=5');

    $response->assertStatus(200)
        ->assertJsonPath('data.data.0.name', 'PT Tetap Sama');
});

test('students eligible select returns only ungraduated siswa', function () {
    $eligibleUser = User::factory()->create(['role' => 'siswa', 'full_name' => 'Siswa Eligible', 'is_active' => true]);
    StudentAlumni::factory()->create([
        'user_id' => $eligibleUser->id,
        'graduation_year' => null,
        'is_active' => true,
    ]);

    $graduatedUser = User::factory()->create(['role' => 'siswa', 'full_name' => 'Siswa Lulus', 'is_active' => true]);
    StudentAlumni::factory()->create([
        'user_id' => $graduatedUser->id,
        'graduation_year' => 2024,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/students?for_select=1&eligible=1&per_page=20');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    ['value', 'label', 'extra'],
                ],
            ],
        ]);

    $extra = $response->json('data.data.0.extra');
    $this->assertSame('Siswa Eligible', $extra['fullName']);
    $this->assertNotEmpty($response->json('data.data.0.value'));
    $this->assertStringContainsString('Siswa Eligible', (string) $response->json('data.data.0.label'));
});

test('students select search filters by nis or name', function () {
    $user = User::factory()->create(['role' => 'siswa', 'full_name' => 'Citra Pelajar', 'is_active' => true]);
    StudentAlumni::factory()->create([
        'user_id' => $user->id,
        'nis' => '99887766',
        'graduation_year' => null,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/students?for_select=1&eligible=1&search=99887766');

    $response->assertStatus(200)->assertJsonCount(1, 'data.data');

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/students?for_select=1&eligible=1&search=Tidak Ada Nama Ini');

    $response->assertStatus(200)->assertJsonCount(0, 'data.data');
});

test('majors and departments select return paginated options', function () {
    Department::create(['code' => 'TIK', 'name' => 'Teknik Informatika', 'is_active' => true]);
    Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak', 'is_active' => true]);

    $majorResponse = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/majors?for_select=1&search=RPL');

    $majorResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.label', 'Rekayasa Perangkat Lunak')
        ->assertJsonPath('data.data.0.extra.code', 'RPL');

    $departmentResponse = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/departments?for_select=1&search=TIK');

    $departmentResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.label', 'Teknik Informatika (TIK)');
});

test('standard types select returns class options with resolved major', function () {
    Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak', 'is_active' => true]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/standard-types?category=class&search=XII RPL 1');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.label', 'XII RPL 1')
        ->assertJsonPath('data.data.0.extra.code', 'xii_rpl_1')
        ->assertJsonPath('data.data.0.extra.resolvedMajorName', 'Rekayasa Perangkat Lunak');
    $this->assertNotEmpty($response->json('data.data.0.value'));
    $this->assertNotEmpty($response->json('data.data.0.extra.resolvedMajorId'));
});

test('standard types select requires known category', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/standard-types?category=kategori_tidak_ada');

    $response->assertStatus(422)->assertJsonValidationErrors(['category']);
});

test('hrd students alumni select returns paginated searchable options', function () {
    $user = User::factory()->create(['role' => 'siswa', 'full_name' => 'Dedi Pelamar', 'is_active' => true]);
    StudentAlumni::factory()->create([
        'user_id' => $user->id,
        'nis' => '11223344',
        'graduation_year' => null,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->hrdUser)
        ->getJson('/api/hrd/students-alumni?search=Dedi&per_page=20');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.extra.nis', '11223344')
        ->assertJsonPath('data.data.0.extra.fullName', 'Dedi Pelamar');
});

test('hrd students alumni select forbidden for siswa', function () {
    $siswa = User::factory()->create(['role' => 'siswa', 'is_active' => true]);

    $this->actingAs($siswa)
        ->getJson('/api/hrd/students-alumni')
        ->assertStatus(403);
});
