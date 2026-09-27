<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\PlacementStatusStandardTypeSeeder;
use Database\Seeders\RecruitmentAttendanceStandardTypeSeeder;
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $siswaUser;

    protected User $hrdUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            PlacementStatusStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
            DepartmentSeeder::class,
            MajorSeeder::class,
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'full_name' => 'Admin BKI',
            'is_active' => true,
        ]);

        $this->siswaUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Siswa Test',
            'is_active' => true,
        ]);

        $this->hrdUser = User::factory()->create([
            'role' => 'hrd',
            'full_name' => 'HRD Test',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertUnauthorized();
    }

    public function test_non_admin_role_cannot_access_admin_dashboard(): void
    {
        $responseSiswa = $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson('/api/admin/dashboard');
        $responseSiswa->assertForbidden();

        $responseHrd = $this->actingAs($this->hrdUser, 'sanctum')
            ->getJson('/api/admin/dashboard');
        $responseHrd->assertForbidden();
    }

    public function test_admin_can_access_dashboard_and_receive_valid_payload(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'metrics' => [
                        'activeStudents',
                        'totalAlumni',
                        'activeVacancies',
                        'applicantsThisMonth',
                        'placedWorkers',
                        'absorptionRate',
                    ],
                    'recruitmentChart' => [
                        '*' => [
                            'month',
                            'melamar',
                            'diterima',
                        ],
                    ],
                    'departmentDistribution' => [
                        '*' => [
                            'name',
                            'code',
                            'count',
                            'percentage',
                        ],
                    ],
                ],
            ]);
    }

    public function test_admin_dashboard_calculates_accurate_metrics_and_chart_values(): void
    {
        $major = \App\Models\Major::first();
        $company = \App\Models\Company::factory()->create(['is_active' => true]);

        // 1. Siswa Aktif (2 orang)
        $userSiswa1 = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        \App\Models\StudentAlumni::factory()->create([
            'user_id' => $userSiswa1->id,
            'major_id' => $major?->id,
            'graduation_year' => null,
        ]);
        $userSiswa2 = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        $student2 = \App\Models\StudentAlumni::factory()->create([
            'user_id' => $userSiswa2->id,
            'major_id' => $major?->id,
            'graduation_year' => null,
        ]);

        // 2. Alumni (2 orang: 1 bekerja, 1 belum)
        $userAlumni1 = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        $alumni1 = \App\Models\StudentAlumni::factory()->create([
            'user_id' => $userAlumni1->id,
            'major_id' => $major?->id,
            'graduation_year' => 2025,
        ]);
        $userAlumni2 = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        \App\Models\StudentAlumni::factory()->create([
            'user_id' => $userAlumni2->id,
            'major_id' => $major?->id,
            'graduation_year' => 2025,
        ]);

        // 1 Tracer Study terisi status 'bekerja'
        \App\Models\TracerStudy::create([
            'student_alumni_id' => $alumni1->id,
            'career_status' => 'bekerja',
            'company_name' => 'PT Mitra Sejahtera',
        ]);

        // 3. Lowongan aktif (1 open)
        $activeVacancy = \App\Models\JobVacancy::create([
            'company_id' => $company->id,
            'title' => 'Teknisi Jaringan',
            'description' => 'Lowongan teknisi',
            'requirements' => 'SMK TKJ',
            'status' => 'published',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'quota' => 5,
            'is_active' => true,
        ]);

        // 4. Lamaran bulan ini
        $application = \App\Models\JobApplication::create([
            'job_vacancy_id' => $activeVacancy->id,
            'student_alumni_id' => $student2->id,
            'applied_at' => now(),
        ]);

        // 5. Hasil seleksi diterima dan published
        \App\Models\SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'published',
            'admin_selection_status' => 'lolos',
        ]);

        // 6. Hasil seleksi kedua berstatus draft (tidak boleh dihitung diterima)
        $application2 = \App\Models\JobApplication::create([
            'job_vacancy_id' => $activeVacancy->id,
            'student_alumni_id' => $student2->id,
            'applied_at' => now(),
        ]);
        \App\Models\SelectionResult::create([
            'job_application_id' => $application2->id,
            'decision' => 'diterima',
            'status' => 'draft',
            'admin_selection_status' => 'lolos',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.metrics.activeStudents', 2)
            ->assertJsonPath('data.metrics.totalAlumni', 2)
            ->assertJsonPath('data.metrics.activeVacancies', 1)
            ->assertJsonPath('data.metrics.applicantsThisMonth', 2)
            ->assertJsonPath('data.metrics.absorptionRate', 50);

        // Titik terakhir chart (bulan ini / indeks ke-5) harus mencatat 2 melamar dan 1 diterima
        $chartData = $response->json('data.recruitmentChart');
        $currentMonthPoint = end($chartData);
        $this->assertEquals(2, $currentMonthPoint['melamar']);
        $this->assertEquals(1, $currentMonthPoint['diterima']);
    }
}
