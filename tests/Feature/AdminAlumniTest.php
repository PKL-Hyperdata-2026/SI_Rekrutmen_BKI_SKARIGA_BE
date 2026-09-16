<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\StudentPortfolioStandardTypeSeeder;
use Database\Seeders\TracerStudyStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAlumniTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Major $rplMajor;

    protected StandardType $classType;

    protected StandardType $employmentStatus;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->rplMajor = Major::where('code', 'RPL')->firstOrFail();
        $this->classType = StandardType::byCategory('class')->firstOrFail();
        $this->employmentStatus = StandardType::byCategory('employment_status')->firstOrFail();

        $this->company = Company::create([
            'name' => 'PT Skariga Solusi Teknologi',
            'is_active' => true,
            'address' => 'Malang',
        ]);
    }

    public function test_options_endpoint_returns_eligible_active_students(): void
    {
        $activeStudentUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Siswa Calon Lulusan',
            'email' => 'calon.lulusan@skariga.sch.id',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        StudentAlumni::create([
            'user_id' => $activeStudentUser->id,
            'nis' => '212200111',
            'major_id' => $this->rplMajor->id,
            'class_id' => $this->classType->id,
            'is_active' => true,
            'graduation_year' => null,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/alumni/options');

        $response->assertOk()
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
                    'eligible_students',
                ],
            ]);
    }

    public function test_can_graduate_active_student_to_alumni(): void
    {
        $activeStudentUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Siswa Aktif Menjadi Alumni',
            'email' => 'siswa.aktif@skariga.sch.id',
            'phone' => '0881036329930',
            'is_active' => true,
        ]);

        StudentAlumni::create([
            'user_id' => $activeStudentUser->id,
            'nis' => '212200112',
            'major_id' => $this->rplMajor->id,
            'class_id' => $this->classType->id,
            'is_active' => true,
            'graduation_year' => null,
        ]);

        $payload = [
            'user_id' => $activeStudentUser->id,
            'nis' => '212200112',
            'full_name' => 'Siswa Aktif Menjadi Alumni',
            'major_id' => $this->rplMajor->id,
            'class_id' => $this->classType->id,
            'graduation_year' => 2026,
            'phone' => '0881036329937',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/alumni', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('students_alumni', [
            'user_id' => $activeStudentUser->id,
            'graduation_year' => 2026,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $activeStudentUser->id,
            'role' => 'alumni',
        ]);
    }

    public function test_can_create_alumni_manually_without_existing_user(): void
    {
        $payload = [
            'user_id' => null,
            'nis' => '212299999',
            'full_name' => 'Budi Alumni Baru',
            'major_id' => $this->rplMajor->id,
            'class_id' => $this->classType->id,
            'graduation_year' => 2024,
            'phone' => '081299998888',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/alumni', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('students_alumni', [
            'nis' => '212299999',
            'graduation_year' => 2024,
        ]);
    }

    public function test_can_create_alumni_with_custom_company_name(): void
    {
        $payload = [
            'user_id' => null,
            'nis' => '212299888',
            'full_name' => 'Alumni Startup Baru',
            'major_id' => $this->rplMajor->id,
            'class_id' => $this->classType->id,
            'graduation_year' => 2025,
            'company_name' => 'PT Teknologi Masa Depan',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/alumni', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('companies', [
            'name' => 'PT Teknologi Masa Depan',
        ]);

        $newCompany = Company::where('name', 'PT Teknologi Masa Depan')->firstOrFail();

        $this->assertDatabaseHas('students_alumni', [
            'nis' => '212299888',
            'current_company_id' => $newCompany->id,
        ]);
    }

    public function test_can_create_manual_alumni_when_user_with_same_email_was_soft_deleted(): void
    {
        $softDeletedUser = User::factory()->create([
            'email' => 'alumni.terhapus@skariga.sch.id',
            'role' => 'siswa',
            'deleted_at' => now(),
        ]);

        $payload = [
            'user_id' => null,
            'nis' => '212299777',
            'email' => 'alumni.terhapus@skariga.sch.id',
            'full_name' => 'Alumni Aktif Kembali',
            'major_id' => $this->rplMajor->id,
            'class_id' => $this->classType->id,
            'graduation_year' => 2025,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/alumni', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'id' => $softDeletedUser->id,
            'email' => 'alumni.terhapus@skariga.sch.id',
            'role' => 'alumni',
            'deleted_at' => null,
        ]);
    }

    public function test_show_endpoint_loads_portfolios_and_root_attributes(): void
    {
        $alumniUser = User::factory()->create([
            'role' => 'alumni',
            'full_name' => 'Alumni Berportofolio',
            'email' => 'berportofolio@skariga.sch.id',
            'phone' => '089912345678',
            'is_active' => true,
        ]);

        $alumni = StudentAlumni::create([
            'user_id' => $alumniUser->id,
            'nis' => '212200555',
            'major_id' => $this->rplMajor->id,
            'class_id' => $this->classType->id,
            'graduation_year' => 2024,
            'is_active' => true,
        ]);

        $portfolioCategory = StandardType::byCategory('portfolio_type')->firstOrFail();

        \App\Models\StudentPortfolio::create([
            'student_alumni_id' => $alumni->id,
            'category_id' => $portfolioCategory->id,
            'title' => 'Sertifikat Magang Fullstack',
            'description' => 'Magang di software house',
            'file_path' => 'portfolios/sertifikat.pdf',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/alumni/{$alumni->id}");

        $response->assertOk()
            ->assertJsonPath('data.fullName', 'Alumni Berportofolio')
            ->assertJsonPath('data.email', 'berportofolio@skariga.sch.id')
            ->assertJsonPath('data.phone', '089912345678')
            ->assertJsonStructure([
                'data' => [
                    'portfolios' => [
                        '*' => [
                            'id',
                            'title',
                            'category' => ['id', 'code', 'name'],
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_update_alumni_profile_and_sync_email_and_company_name(): void
    {
        $alumniUser = User::factory()->create([
            'role' => 'alumni',
            'full_name' => 'Nama Lama',
            'email' => 'lama@skariga.sch.id',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $alumni = StudentAlumni::create([
            'user_id' => $alumniUser->id,
            'nis' => '212200666',
            'major_id' => $this->rplMajor->id,
            'class_id' => $this->classType->id,
            'graduation_year' => 2024,
            'is_active' => true,
        ]);

        $payload = [
            'full_name' => 'Nama Baru Alumni',
            'email' => 'baru@skariga.sch.id',
            'phone' => '089988776655',
            'company_name' => 'PT Perusahaan Terupdate',
            'graduation_year' => 2024,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/alumni/{$alumni->id}", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $alumniUser->id,
            'full_name' => 'Nama Baru Alumni',
            'email' => 'baru@skariga.sch.id',
            'phone' => '089988776655',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'PT Perusahaan Terupdate',
        ]);
    }
}
