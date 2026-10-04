<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPlacement;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\Notification;
use App\Models\SelectionResult;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\ClassSeeder;
use Database\Seeders\CompanyIndustrySeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\PlacementStatusStandardTypeSeeder;
use Database\Seeders\RecruitmentAttendanceStandardTypeSeeder;
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Database\Seeders\StudentPortfolioStandardTypeSeeder;
use Database\Seeders\TracerStudyStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoPlacementOnAcceptedTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrdUser;

    protected Company $company;

    protected User $studentUser;

    protected StudentAlumni $studentProfile;

    protected JobVacancy $jobVacancy;

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
            ApplicationStageHistoryStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
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
            'name' => 'PT Manufaktur Unggul',
            'is_active' => true,
            'email' => 'hrd@manufaktur.test',
        ]);

        $major = Major::firstOrFail();

        $this->studentUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Pelamar Unggul',
            'is_active' => true,
        ]);

        $this->studentProfile = StudentAlumni::create([
            'user_id' => $this->studentUser->id,
            'major_id' => $major->id,
            'nis' => '998877',
            'is_active' => true,
        ]);

        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->firstOrFail();

        $this->jobVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Teknisi Produksi',
            'position' => 'Teknisi Produksi',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $targetBoth->id,
            'quota' => 3,
            'is_active' => true,
            'created_by' => $this->hrdUser->id,
        ]);
        $this->jobVacancy->majors()->attach($major->id);
    }

    public function test_publishing_accepted_result_automatically_creates_job_placement(): void
    {
        $pendingStatus = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();

        $application = JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $pendingStatus->id,
            'applied_at' => now(),
        ]);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'admin_selection_status' => 'lolos',
            'decision' => 'diterima',
            'status' => 'draft',
            'notes' => 'Kandidat diterima bekerja',
            'final_score' => 95.0,
        ]);

        $response = $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', [
                'job_vacancy_id' => $this->jobVacancy->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $placement = JobPlacement::where('job_application_id', $application->id)->first();
        $this->assertNotNull($placement);
        $this->assertSame($this->studentProfile->id, $placement->student_alumni_id);
        $this->assertSame($this->company->id, $placement->company_id);

        $this->studentProfile->refresh();
        $this->assertSame($this->company->id, $this->studentProfile->current_company_id);
        $this->assertSame('Teknisi Produksi', $this->studentProfile->current_position);

        $notifications = Notification::where('user_id', $this->studentUser->id)->get();
        $types = $notifications->pluck('type')->all();
        $this->assertContains('selection_accepted', $types);
        $this->assertContains('tracer_study_reminder', $types);
    }
}
