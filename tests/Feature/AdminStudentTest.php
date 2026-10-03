<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\StudentPortfolio;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\StudentPortfolioStandardTypeSeeder;
use Database\Seeders\TracerStudyStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        MajorSeeder::class,
        ClassSeeder::class,
        TracerStudyStandardTypeSeeder::class,
        StudentPortfolioStandardTypeSeeder::class,
    ]);

    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->siswaUser = User::factory()->create([
        'role' => 'siswa',
        'is_active' => true,
    ]);

    $this->rplMajor = Major::where('code', 'RPL')->firstOrFail();
    $this->classType = StandardType::byCategory('class')->firstOrFail();
    $this->employmentStatus = StandardType::byCategory('employment_status')->firstOrFail();
    $this->portfolioType = StandardType::byCategory('portfolio_type')->firstOrFail();

    $this->company = Company::create([
        'name' => 'PT Aldis Burger',
        'is_active' => true,
        'address' => 'Jakarta Selatan',
    ]);
});

test('admin can fetch student options', function () {
    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->getJson('/api/admin/students/options');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'companies',
                'majors',
                'classes',
                'employment_statuses',
                'portfolio_types',
                'graduation_years',
            ],
        ]);
});

test('admin can create student with user account', function () {
    $payload = [
        'nis' => '212200881',
        'full_name' => 'Aldi Taher Lucy',
        'email' => 'alditaher.katanya@gmail.com',
        'phone' => '0881036329937',
        'class_id' => $this->classType->id,
        'major_id' => $this->rplMajor->id,
        'employment_status_id' => $this->employmentStatus->id,
        'graduation_year' => 2026,
        'social_media' => [
            'profile_url' => 'https://linkedin.com/in/aldi-taher-skariga',
        ],
        'current_company_id' => $this->company->id,
    ];

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->postJson('/api/admin/students', $payload);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nis', '212200881')
        ->assertJsonPath('data.fullName', 'Aldi Taher Lucy')
        ->assertJsonPath('data.email', 'alditaher.katanya@gmail.com');

    $this->assertDatabaseHas('users', [
        'email' => 'alditaher.katanya@gmail.com',
        'full_name' => 'Aldi Taher Lucy',
        'role' => 'siswa',
    ]);

    $this->assertDatabaseHas('students_alumni', [
        'nis' => '212200881',
        'major_id' => $this->rplMajor->id,
        'class_id' => $this->classType->id,
    ]);
});

test('admin can list and search students', function () {
    $user1 = User::factory()->create(['full_name' => 'Aldi Taher', 'role' => 'siswa']);
    StudentAlumni::create([
        'user_id' => $user1->id,
        'nis' => '212200881',
        'major_id' => $this->rplMajor->id,
        'class_id' => $this->classType->id,
    ]);

    $user2 = User::factory()->create(['full_name' => 'Marvello Cikiwaw', 'role' => 'siswa']);
    StudentAlumni::create([
        'user_id' => $user2->id,
        'nis' => '212200882',
        'major_id' => $this->rplMajor->id,
        'class_id' => $this->classType->id,
    ]);

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->getJson('/api/admin/students?search=Aldi');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.nis', '212200881');
});

test('admin can view single student detail with portfolios', function () {
    $user = User::factory()->create(['full_name' => 'Aldi Taher Juicy', 'role' => 'siswa']);
    $student = StudentAlumni::create([
        'user_id' => $user->id,
        'nis' => '212210045',
        'major_id' => $this->rplMajor->id,
        'class_id' => $this->classType->id,
    ]);

    StudentPortfolio::create([
        'student_alumni_id' => $student->id,
        'category_id' => $this->portfolioType->id,
        'title' => 'Curriculum Vitae (CV)',
        'file_path' => 'portfolios/test_cv.pdf',
    ]);

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->getJson("/api/admin/students/{$student->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nis', '212210045')
        ->assertJsonCount(1, 'data.portfolios')
        ->assertJsonPath('data.portfolios.0.title', 'Curriculum Vitae (CV)');
});

test('admin can update student and synced user', function () {
    $user = User::factory()->create(['full_name' => 'Old Name', 'role' => 'siswa']);
    $student = StudentAlumni::create([
        'user_id' => $user->id,
        'nis' => '212210045',
        'major_id' => $this->rplMajor->id,
        'class_id' => $this->classType->id,
    ]);

    $updatePayload = [
        'nis' => '212210045',
        'full_name' => 'Updated Name',
        'email' => 'updated@gmail.com',
        'phone' => '08123456789',
        'class_id' => $this->classType->id,
        'major_id' => $this->rplMajor->id,
    ];

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->putJson("/api/admin/students/{$student->id}", $updatePayload);

    $response->assertOk()
        ->assertJsonPath('data.fullName', 'Updated Name')
        ->assertJsonPath('data.email', 'updated@gmail.com');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'full_name' => 'Updated Name',
        'email' => 'updated@gmail.com',
    ]);
});

test('admin can upload and delete student portfolio', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => 'siswa']);
    $student = StudentAlumni::create([
        'user_id' => $user->id,
        'nis' => '212210045',
        'major_id' => $this->rplMajor->id,
        'class_id' => $this->classType->id,
    ]);

    $file = UploadedFile::fake()->create('CV_AldiTaher.pdf', 1200, 'application/pdf');

    $uploadResponse = $this->actingAs($this->adminUser, 'sanctum')
        ->postJson("/api/admin/students/{$student->id}/portfolios", [
            'category_id' => $this->portfolioType->id,
            'title' => 'Curriculum Vitae (CV)',
            'file' => $file,
        ]);

    $uploadResponse->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.portfolios')
        ->assertJsonPath('data.portfolios.0.fileName', 'CV_AldiTaher.pdf')
        ->assertJsonPath('data.portfolios.0.originalFilename', 'CV_AldiTaher.pdf');

    $portfolioId = $uploadResponse->json('data.portfolios.0.id');

    $deleteResponse = $this->actingAs($this->adminUser, 'sanctum')
        ->deleteJson("/api/admin/students/{$student->id}/portfolios/{$portfolioId}");

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('student_portfolios', [
        'id' => is_numeric($portfolioId) ? $portfolioId : (int) decrypt($portfolioId),
    ]);

});

test('admin can soft delete student', function () {
    $user = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
    $student = StudentAlumni::create([
        'user_id' => $user->id,
        'nis' => '212210045',
        'major_id' => $this->rplMajor->id,
        'class_id' => $this->classType->id,
    ]);

    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->deleteJson("/api/admin/students/{$student->id}");

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('students_alumni', [
        'id' => $student->id,
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_active' => false,
    ]);
});

test('non admin cannot access student routes', function () {
    $response = $this->actingAs($this->siswaUser, 'sanctum')
        ->getJson('/api/admin/students');

    $response->assertForbidden();
});
