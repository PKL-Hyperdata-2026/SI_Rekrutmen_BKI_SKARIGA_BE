<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use App\Services\ActivityScoreService;
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

class ActivityScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;

    protected StudentAlumni $studentProfile;

    protected ActivityScoreService $scoreService;

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
            'full_name' => 'Siswa Aktif',
            'is_active' => true,
        ]);

        $this->studentProfile = StudentAlumni::create([
            'user_id' => $this->studentUser->id,
            'major_id' => $major->id,
            'nis' => '112244',
            'is_active' => true,
        ]);

        $this->scoreService = app(ActivityScoreService::class);
    }

    public function test_log_activity_creates_record(): void
    {
        $log = $this->scoreService->logActivity(
            $this->studentUser->id,
            'login',
            'auth',
            User::class,
            $this->studentUser->id,
            ['device' => 'web']
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertSame($this->studentUser->id, $log->user_id);
        $this->assertSame('login', $log->action);
    }

    public function test_calculate_score_aggregates_user_activities(): void
    {
        $this->scoreService->logActivity($this->studentUser->id, 'login', 'auth');
        $this->scoreService->logActivity($this->studentUser->id, 'complete_profile', 'portfolio');
        $this->scoreService->logActivity($this->studentUser->id, 'apply_vacancy', 'recruitment');
        $this->scoreService->logActivity($this->studentUser->id, 'attend_test', 'attendance');

        $score = $this->scoreService->calculateScore($this->studentUser->id);

        $expected = 5 + 15 + 20 + 30;
        $this->assertSame($expected, $score);
    }

    public function test_student_applying_to_vacancy_records_activity_log(): void
    {
        $hrd = User::factory()->create(['role' => 'hrd']);
        $company = Company::create([
            'user_id' => $hrd->id,
            'name' => 'PT Karir Cemerlang',
            'is_active' => true,
        ]);

        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->firstOrFail();

        $vacancy = JobVacancy::create([
            'company_id' => $company->id,
            'title' => 'Staf Administrasi',
            'position' => 'Staf Administrasi',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $targetBoth->id,
            'quota' => 2,
            'is_active' => true,
        ]);
        $vacancy->majors()->attach($this->studentProfile->major_id);

        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$vacancy->id}/apply");

        $response->assertStatus(201);

        $hasLog = ActivityLog::where('user_id', $this->studentUser->id)
            ->where('action', 'apply_vacancy')
            ->exists();

        $this->assertTrue($hasLog);
    }
}
