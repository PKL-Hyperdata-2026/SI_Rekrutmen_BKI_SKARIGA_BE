<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApplicationStageHistory;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\RecruitmentAttendance;
use App\Models\SelectionResult;
use App\Models\SelectionStage;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\RecruitmentAttendanceStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HrdSelectionResultTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrdUserA;

    protected Company $companyA;

    protected User $hrdUserB;

    protected Company $companyB;

    protected User $adminUser;

    protected User $studentUser;

    protected StudentAlumni $studentAlumni;

    protected JobVacancy $vacancyA;

    protected SelectionStage $stageA;

    protected JobApplication $application;

    protected ApplicationStageHistory $stageHistory;

    protected RecruitmentAttendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            JobVacancyStandardTypeSeeder::class,
            MajorSeeder::class,
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
        ]);

        $this->hrdUserA = User::factory()->create([
            'role' => 'hrd',
            'is_active' => true,
        ]);
        $this->companyA = Company::create([
            'user_id' => $this->hrdUserA->id,
            'name' => 'PT Astra SKARIGA',
            'is_active' => true,
        ]);

        $this->hrdUserB = User::factory()->create([
            'role' => 'hrd',
            'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'user_id' => $this->hrdUserB->id,
            'name' => 'PT Manufaktur Lain',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->studentUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $major = Major::firstOrFail();
        $this->studentAlumni = StudentAlumni::create([
            'user_id' => $this->studentUser->id,
            'major_id' => $major->id,
            'nis' => '25073',
            'graduation_year' => 2025,
            'is_active' => true,
        ]);

        $this->vacancyA = JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Junior Mechanic Operator',
            'position' => 'Junior Mechanic',
            'description' => 'Test lowongan',
            'requirements' => 'Bisa mesin',
            'quota' => 5,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'is_active' => true,
        ]);

        $this->stageA = SelectionStage::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'name' => 'Psikotes & Akademik',
            'sequence_order' => 1,
            'scheduled_at' => now()->addDays(2),
            'location' => 'SMK PGRI 1 GIRI',
        ]);

        $inProgressStatus = StandardType::byCategory('job_application_status')->where('code', 'in_progress')->firstOrFail();
        $this->application = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentAlumni->id,
            'status_id' => $inProgressStatus->id,
            'current_stage_id' => $this->stageA->id,
            'applied_at' => now(),
        ]);

        $scheduledStatus = StandardType::byCategory('application_stage_status')->where('code', 'scheduled')->firstOrFail();
        $this->stageHistory = ApplicationStageHistory::create([
            'job_application_id' => $this->application->id,
            'selection_stage_id' => $this->stageA->id,
            'status_id' => $scheduledStatus->id,
        ]);

        $this->attendance = RecruitmentAttendance::create([
            'stage_history_id' => $this->stageHistory->id,
            'validation_status' => 'pending',
        ]);
    }

    public function test_hrd_can_view_selection_results_list_and_summary(): void
    {
        $response = $this->actingAs($this->hrdUserA)
            ->getJson('/api/hrd/selection-results?job_vacancy_id='.$this->vacancyA->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'total',
                        'lolos',
                        'gagal',
                        'cadangan',
                    ],
                    'applicants',
                    'filters' => [
                        'vacancies',
                        'decisions',
                    ],
                ],
            ]);

        $this->assertEquals(1, $response->json('data.summary.total'));
    }

    public function test_hrd_can_save_evaluation_scores_and_letter(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('surat-penempatan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->hrdUserA)
            ->postJson('/api/hrd/selection-results/'.$this->application->id, [
                'psychotest_score' => 80.19,
                'interview_score' => 90.50,
                'mcu_score' => 83.20,
                'decision' => 'diterima',
                'notes' => 'Siswa memiliki kedisiplinan tinggi',
                'letter_file' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $result = SelectionResult::where('job_application_id', $this->application->id)->first();
        $this->assertNotNull($result);
        $this->assertEquals('diterima', $result->decision);
        $this->assertEquals(80.19, (float) $result->psychotest_score);
        $this->assertEquals(90.50, (float) $result->interview_score);
        $this->assertEquals(83.20, (float) $result->mcu_score);
        $this->assertEquals(84.63, (float) $result->final_score);
        $this->assertNotNull($result->letter_path);

        $this->attendance->refresh();
        $this->assertEquals('verified', $this->attendance->validation_status);
        $this->assertEquals('present', $this->attendance->attendanceStatus?->code);
    }

    public function test_hrd_can_update_decision_inline(): void
    {
        $response = $this->actingAs($this->hrdUserA)
            ->patchJson('/api/hrd/selection-results/'.$this->application->id.'/decision', [
                'decision' => 'cadangan',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $result = SelectionResult::where('job_application_id', $this->application->id)->first();
        $this->assertNotNull($result);
        $this->assertEquals('cadangan', $result->decision);
    }

    public function test_hrd_can_publish_results_and_sync(): void
    {
        SelectionResult::create([
            'job_application_id' => $this->application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->postJson('/api/hrd/selection-results/publish', [
                'job_vacancy_id' => $this->vacancyA->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.published_count', 1);

        $result = SelectionResult::where('job_application_id', $this->application->id)->first();
        $this->assertEquals('published', $result->status);

        $this->application->refresh();
        $this->assertEquals('accepted', $this->application->status?->code);
    }

    public function test_hrd_can_save_draft(): void
    {
        SelectionResult::create([
            'job_application_id' => $this->application->id,
            'decision' => 'pending',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->postJson('/api/hrd/selection-results/draft', [
                'job_vacancy_id' => $this->vacancyA->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $result = SelectionResult::where('job_application_id', $this->application->id)->first();
        $this->assertEquals('draft', $result->status);
    }
}
