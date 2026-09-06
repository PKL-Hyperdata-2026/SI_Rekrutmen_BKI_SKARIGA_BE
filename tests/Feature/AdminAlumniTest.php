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
}
