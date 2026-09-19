<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApplicationStageHistory;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPlacement;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\SelectionResult;
use App\Models\SelectionStage;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\PlacementStatusStandardTypeSeeder;
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentJobApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;

    protected StudentAlumni $studentProfile;

    protected User $otherStudentUser;

    protected StudentAlumni $otherStudentProfile;

    protected User $adminUser;

    protected User $hrdUser;

    protected Company $company;

    protected JobVacancy $jobVacancy;

    protected StandardType $appStatusPending;

    protected StandardType $appStatusAccepted;

    protected StandardType $stageStatusPassed;

    protected StandardType $stageTypeAdmin;

    protected SelectionStage $stage1;

    protected SelectionStage $stage2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            PlacementStatusStandardTypeSeeder::class,
            MajorSeeder::class,
        ]);

        $this->studentUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Siswa Test',
            'is_active' => true,
        ]);

        $major = Major::firstOrFail();

        $this->studentProfile = StudentAlumni::create([
            'user_id' => $this->studentUser->id,
            'major_id' => $major->id,
            'nis' => '11223344',
            'is_active' => true,
        ]);

        $this->otherStudentUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Other Siswa Test',
            'is_active' => true,
        ]);

        $this->otherStudentProfile = StudentAlumni::create([
            'user_id' => $this->otherStudentUser->id,
            'major_id' => $major->id,
            'nis' => '99887766',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->hrdUser = User::factory()->create([
            'role' => 'hrd',
            'full_name' => 'HRD Penguji',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'name' => 'PT Teknologi Nusantara',
            'is_active' => true,
            'address' => 'Jl. Sudirman No. 10 Jakarta',
            'logo_path' => 'logos/company1.png',
        ]);

        $jobType = StandardType::byCategory('job_type')->first();
        $vacancyStatus = StandardType::byCategory('vacancy_status')->first();

        $this->jobVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'job_type_id' => $jobType?->id,
            'status_id' => $vacancyStatus?->id,
            'title' => 'Junior Web Developer',
            'slug' => 'junior-web-developer',
            'position' => 'Developer',
            'work_location' => 'Jakarta Selatan',
            'is_active' => true,
        ]);

        $this->appStatusPending = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();
        $this->appStatusAccepted = StandardType::byCategory('job_application_status')->where('code', 'accepted')->firstOrFail();
        $this->stageStatusPassed = StandardType::byCategory('application_stage_status')->where('code', 'passed')->firstOrFail();
        $this->stageTypeAdmin = StandardType::byCategory('stage_type')->firstOrFail();

        $this->stage1 = SelectionStage::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'stage_type_id' => $this->stageTypeAdmin->id,
            'name' => 'Seleksi Berkas Administrasi',
            'sequence_order' => 1,
        ]);

        $this->stage2 = SelectionStage::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'stage_type_id' => $this->stageTypeAdmin->id,
            'name' => 'Tes Teknis & Coding',
            'sequence_order' => 2,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_my_applications(): void
    {
        $response = $this->getJson('/api/my-applications');
        $response->assertUnauthorized();
    }

    public function test_non_student_or_alumni_role_cannot_access_my_applications(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/my-applications');

        $response->assertForbidden();

        $hrdResponse = $this->actingAs($this->hrdUser, 'sanctum')
            ->getJson('/api/my-applications');

        $hrdResponse->assertForbidden();
    }

    public function test_student_can_list_own_applications(): void
    {
        $app1 = JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $this->appStatusPending->id,
            'current_stage_id' => $this->stage1->id,
            'applied_at' => now(),
            'notes' => 'Lamaran pertama',
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/my-applications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.id', $app1->id)
            ->assertJsonPath('data.data.0.vacancy.title', 'Junior Web Developer')
            ->assertJsonPath('data.data.0.vacancy.companyName', 'PT Teknologi Nusantara')
            ->assertJsonPath('data.data.0.status.code', 'pending')
            ->assertJsonPath('data.data.0.currentStage.name', 'Seleksi Berkas Administrasi')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'jobVacancyId',
                            'vacancy' => [
                                'id',
                                'title',
                                'position',
                                'companyName',
                                'companyLogo',
                                'workLocation',
                                'deadline',
                            ],
                            'status' => [
                                'id',
                                'name',
                                'code',
                            ],
                            'currentStage' => [
                                'id',
                                'name',
                                'order',
                                'scheduledAt',
                                'location',
                                'instructions',
                            ],
                            'appliedAt',
                            'notes',
                            'createdAt',
                        ],
                    ],
                ],
            ]);
    }

    public function test_student_can_filter_applications_by_status(): void
    {
        $appPending = JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $this->appStatusPending->id,
            'applied_at' => now()->subDays(2),
        ]);

        $appAccepted = JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $this->appStatusAccepted->id,
            'applied_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/my-applications?status_id={$this->appStatusAccepted->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $appAccepted->id)
            ->assertJsonPath('data.data.0.status.code', 'accepted');
    }

    public function test_student_can_view_single_application_detail_with_stage_histories(): void
    {
        $application = JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $this->appStatusPending->id,
            'current_stage_id' => $this->stage2->id,
            'applied_at' => now(),
            'notes' => 'Catatan lamaran',
        ]);

        $history1 = ApplicationStageHistory::create([
            'job_application_id' => $application->id,
            'selection_stage_id' => $this->stage1->id,
            'status_id' => $this->stageStatusPassed->id,
            'assessor_id' => $this->hrdUser->id,
            'score' => 88.50,
            'notes' => 'Berkas lengkap dan sesuai kriteria',
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/my-applications/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $application->id)
            ->assertJsonPath('data.vacancy.title', 'Junior Web Developer')
            ->assertJsonPath('data.stageHistories.0.id', $history1->id)
            ->assertJsonPath('data.stageHistories.0.stage.name', 'Seleksi Berkas Administrasi')
            ->assertJsonPath('data.stageHistories.0.stage.order', 1)
            ->assertJsonPath('data.stageHistories.0.status.code', 'passed')
            ->assertJsonPath('data.stageHistories.0.assessorName', 'HRD Penguji')
            ->assertJsonPath('data.stageHistories.0.score', 88.5)
            ->assertJsonPath('data.stageHistories.0.notes', 'Berkas lengkap dan sesuai kriteria')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'jobVacancyId',
                    'vacancy',
                    'status',
                    'currentStage',
                    'appliedAt',
                    'notes',
                    'stageHistories' => [
                        '*' => [
                            'id',
                            'stage' => [
                                'id',
                                'name',
                                'order',
                            ],
                            'status' => [
                                'id',
                                'name',
                                'code',
                            ],
                            'assessorName',
                            'score',
                            'notes',
                            'createdAt',
                        ],
                    ],
                    'createdAt',
                ],
            ]);
    }

    public function test_student_cannot_access_application_of_another_student(): void
    {
        $otherApplication = JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->otherStudentProfile->id,
            'status_id' => $this->appStatusPending->id,
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/my-applications/{$otherApplication->id}");

        $response->assertNotFound();
    }

    public function test_student_can_view_application_detail_with_placement_and_selection_result(): void
    {
        $application = JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $this->appStatusAccepted->id,
            'current_stage_id' => $this->stage2->id,
            'applied_at' => now(),
            'notes' => 'Lamaran diterima',
        ]);

        $placementStatus = StandardType::whereHas('category', fn ($q) => $q->where('code', 'placement_status'))->firstOrFail();

        $placement = JobPlacement::create([
            'job_application_id' => $application->id,
            'student_alumni_id' => $this->studentProfile->id,
            'company_id' => $this->company->id,
            'placement_status_id' => $placementStatus->id,
            'accepted_date' => '2026-09-01',
            'start_date' => '2026-10-01',
            'notes' => 'Penempatan divisi IT',
        ]);

        $selectionResult = SelectionResult::create([
            'job_application_id' => $application->id,
            'admin_selection_status' => 'lolos',
            'decision' => 'diterima',
            'status' => 'published',
            'notes' => 'Lulus semua tahapan',
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/my-applications/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $application->id)
            ->assertJsonPath('data.placement.id', $placement->id)
            ->assertJsonPath('data.placement.acceptedDate', '2026-09-01')
            ->assertJsonPath('data.placement.startDate', '2026-10-01')
            ->assertJsonPath('data.placement.notes', 'Penempatan divisi IT')
            ->assertJsonPath('data.selectionResult.id', $selectionResult->id)
            ->assertJsonPath('data.selectionResult.decision', 'diterima');
    }

    public function test_student_can_list_job_vacancies_with_has_applied_field(): void
    {
        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/job-vacancies');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.hasApplied', false);

        JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $this->appStatusPending->id,
            'applied_at' => now(),
        ]);

        $appliedResponse = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/job-vacancies');

        $appliedResponse->assertOk()
            ->assertJsonPath('data.data.0.hasApplied', true);
    }

    public function test_student_can_view_job_vacancy_detail_with_has_applied(): void
    {
        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/job-vacancies/{$this->jobVacancy->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hasApplied', false);

        JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $this->appStatusPending->id,
            'applied_at' => now(),
        ]);

        $appliedResponse = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/job-vacancies/{$this->jobVacancy->id}");

        $appliedResponse->assertOk()
            ->assertJsonPath('data.hasApplied', true);
    }

    public function test_student_filter_options_only_include_available_data_in_student_vacancies(): void
    {
        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/job-vacancies/options');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'departments',
                    'companies',
                    'majors',
                    'targetApplicants',
                    'workLocations',
                ],
            ]);
    }

    public function test_student_can_list_job_vacancies_sorted_asc(): void
    {
        $status = StandardType::byCategory('vacancy_status')->where('code', 'published')->first()
            ?? StandardType::byCategory('vacancy_status')->first();

        JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Second Asc Vacancy',
            'slug' => 'second-asc-vacancy',
            'position' => 'Developer',
            'status_id' => $status?->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/job-vacancies');

        $response->assertOk();
        $items = $response->json('data.data');
        $this->assertGreaterThanOrEqual(2, count($items));
        $firstId = (int) decrypt((string) $items[0]['id']);
        $secondId = (int) decrypt((string) $items[1]['id']);
        $this->assertLessThan($secondId, $firstId);
    }
}
