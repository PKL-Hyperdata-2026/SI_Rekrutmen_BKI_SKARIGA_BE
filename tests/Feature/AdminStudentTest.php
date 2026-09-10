<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\StudentPortfolio;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\StudentPortfolioStandartTypeSeeder;
use Database\Seeders\TracerStudyStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminStudentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $siswaUser;

    protected Major $rplMajor;

    protected StandardType $classType;

    protected StandardType $employmentStatus;

    protected StandardType $portfolioType;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MajorSeeder::class,
            ClassSeeder::class,
            TracerStudyStandardTypeSeeder::class,
            StudentPortfolioStandartTypeSeeder::class,
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
    }

    public function test_admin_can_fetch_student_options(): void
    {
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
    }

    public function test_admin_can_create_student_with_user_account(): void
    {
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
    }

    public function test_admin_can_list_and_search_students(): void
    {
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
    }

    public function test_admin_can_view_single_student_detail_with_portfolios(): void
    {
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
    }

    public function test_admin_can_update_student_and_synced_user(): void
    {
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
    }

    public function test_admin_can_upload_and_delete_student_portfolio(): void
    {
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

    }

    public function test_admin_can_soft_delete_student(): void
    {
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
    }

    public function test_non_admin_cannot_access_student_routes(): void
    {
        $response = $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson('/api/admin/students');

        $response->assertForbidden();
    }
}
