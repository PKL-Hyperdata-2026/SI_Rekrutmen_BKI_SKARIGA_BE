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

class HrdDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrdUser;

    protected Company $company;

    protected User $otherHrdUser;

    protected Company $otherCompany;

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

        $this->hrdUser = User::factory()->create([
            'role' => 'hrd',
            'full_name' => 'HRD Tester',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'user_id' => $this->hrdUser->id,
            'name' => 'PT Sumber Makmur',
            'is_active' => true,
        ]);

        $this->otherHrdUser = User::factory()->create([
            'role' => 'hrd',
            'full_name' => 'Other HRD',
            'is_active' => true,
        ]);

        $this->otherCompany = Company::create([
            'user_id' => $this->otherHrdUser->id,
            'name' => 'PT Pesaing Maju',
            'is_active' => true,
        ]);
    }

    public function test_hrd_can_get_dashboard_data_scoped_to_company(): void
    {
        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->firstOrFail();
        $pendingStatus = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();

        $major = Major::firstOrFail();

        $myVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Software Tester',
            'position' => 'Software Tester',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $targetBoth->id,
            'quota' => 5,
            'deadline' => now()->addDays(20),
            'is_active' => true,
            'created_by' => $this->hrdUser->id,
        ]);
        $myVacancy->majors()->attach($major->id);

        $otherVacancy = JobVacancy::create([
            'company_id' => $this->otherCompany->id,
            'title' => 'Other Job',
            'position' => 'Other Job',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $targetBoth->id,
            'quota' => 10,
            'is_active' => true,
            'created_by' => $this->otherHrdUser->id,
        ]);

        $student = User::factory()->create(['role' => 'siswa']);
        $studentProfile = StudentAlumni::create([
            'user_id' => $student->id,
            'major_id' => $major->id,
            'nis' => '123412',
            'is_active' => true,
        ]);

        JobApplication::create([
            'job_vacancy_id' => $myVacancy->id,
            'student_alumni_id' => $studentProfile->id,
            'status_id' => $pendingStatus->id,
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($this->hrdUser, 'sanctum')
            ->getJson('/api/hrd/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metrics.active_vacancies', 1)
            ->assertJsonPath('data.metrics.total_quota', 5)
            ->assertJsonPath('data.metrics.total_applicants', 1);

        $vacancies = $response->json('data.active_vacancies');
        $this->assertCount(1, $vacancies);
        $this->assertSame('Software Tester', $vacancies[0]['title']);

        $applicants = $response->json('data.recent_applicants');
        $this->assertCount(1, $applicants);
        $this->assertSame($student->full_name, $applicants[0]['name']);
    }

    public function test_non_hrd_cannot_access_hrd_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/hrd/dashboard')
            ->assertStatus(403);
    }
}
