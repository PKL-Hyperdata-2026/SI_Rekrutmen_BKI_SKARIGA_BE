<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\StandardType;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
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
            SelectionStageStandardTypeSeeder::class,
            JobVacancyStandardTypeSeeder::class,
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

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $this->getJson('/api/admin/reports/options')->assertUnauthorized();
        $this->getJson('/api/admin/reports/recruitment')->assertUnauthorized();
        $this->getJson('/api/admin/reports/attendance')->assertUnauthorized();
        $this->getJson('/api/admin/reports/absorption')->assertUnauthorized();
        $this->getJson('/api/admin/reports/tracer-study')->assertUnauthorized();
    }

    public function test_non_admin_user_cannot_access_reports(): void
    {
        $this->actingAs($this->siswaUser)
            ->getJson('/api/admin/reports/options')
            ->assertForbidden();
    }

    public function test_admin_can_get_report_options(): void
    {
        Company::factory()->create(['name' => 'PT Astra Honda Motor', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/reports/options');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'companies',
                    'majors',
                    'graduation_years',
                ],
            ]);
    }

    public function test_admin_can_get_recruitment_report(): void
    {
        $company = Company::factory()->create(['name' => 'PT Astra Honda Motor', 'is_active' => true]);
        $targetApplicant = StandardType::byCategory('target_applicant')->first();
        $vacancyStatus = StandardType::byCategory('vacancy_status')->first();

        JobVacancy::create([
            'company_id' => $company->id,
            'title' => 'Junior Mechanic',
            'position' => 'Junior Mechanic',
            'target_applicant_id' => $targetApplicant?->id,
            'status_id' => $vacancyStatus?->id,
            'quota' => 10,
            'deadline' => now()->addDays(30),
            'work_location' => 'Malang',
            'qualification' => 'Lulusan SMK',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/reports/recruitment');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'metrics' => [
                        'total_applicants',
                        'total_accepted',
                        'pass_rate',
                        'active_companies',
                    ],
                    'data',
                ],
            ]);
    }

    public function test_admin_can_get_attendance_report(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/reports/attendance');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'metrics' => [
                        'sosialisasi_rate',
                        'psikotes_rate',
                        'interview_rate',
                    ],
                    'data',
                ],
            ]);
    }

    public function test_admin_can_get_absorption_report(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/reports/absorption');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'metrics' => [
                        'class_12_rate',
                        'alumni_rate',
                        'working_dudi_rate',
                        'study_entrepreneur_rate',
                    ],
                    'data',
                ],
            ]);
    }

    public function test_admin_can_get_tracer_study_report(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/reports/tracer-study');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'metrics' => [
                        'avg_waiting_time',
                        'industries_count',
                        'regions_count',
                    ],
                    'data',
                ],
            ]);
    }

    public function test_admin_can_filter_reports_with_partial_dates(): void
    {
        $responseStartOnly = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/reports/recruitment?start_date=2026-01-01');

        $responseStartOnly->assertOk();

        $responseEndOnly = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/reports/recruitment?end_date=2026-12-31');

        $responseEndOnly->assertOk();
    }
}

