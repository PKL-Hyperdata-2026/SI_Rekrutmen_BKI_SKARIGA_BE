<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\CompanyIndustrySeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\PlacementStatusStandardTypeSeeder;
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Database\Seeders\StudentPortfolioStandardTypeSeeder;
use Database\Seeders\TracerStudyStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;

    protected StudentAlumni $studentProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CompanyIndustrySeeder::class,
            ClassSeeder::class,
            MajorSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
            JobApplicationStandardTypeSeeder::class,
            TracerStudyStandardTypeSeeder::class,
            PlacementStatusStandardTypeSeeder::class,
            StudentPortfolioStandardTypeSeeder::class,
        ]);

        $major = Major::firstOrFail();

        $this->studentUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Siswa Analitik',
            'is_active' => true,
        ]);

        $this->studentProfile = StudentAlumni::create([
            'user_id' => $this->studentUser->id,
            'major_id' => $major->id,
            'nis' => '113355',
            'is_active' => true,
        ]);

        $hrd = User::factory()->create(['role' => 'hrd']);
        $company = Company::create([
            'user_id' => $hrd->id,
            'name' => 'PT Solusi Tren',
            'is_active' => true,
        ]);

        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->firstOrFail();

        $vacancy = JobVacancy::create([
            'company_id' => $company->id,
            'title' => 'Backend Junior',
            'position' => 'Backend Junior',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $targetBoth->id,
            'quota' => 2,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $pendingStatus = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();

        JobApplication::create([
            'job_vacancy_id' => $vacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $pendingStatus->id,
            'applied_at' => now(),
        ]);
    }

    public function test_student_can_get_monthly_trends_data(): void
    {
        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/siswa/dashboard/trends');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $trends = $response->json('data');
        $this->assertCount(6, $trends);
        $this->assertArrayHasKey('month', $trends[0]);
        $this->assertArrayHasKey('lowongan', $trends[0]);
        $this->assertArrayHasKey('melamar', $trends[0]);

        $currentMonthData = end($trends);
        $this->assertGreaterThanOrEqual(1, $currentMonthData['lowongan']);
        $this->assertGreaterThanOrEqual(1, $currentMonthData['melamar']);
    }
}
