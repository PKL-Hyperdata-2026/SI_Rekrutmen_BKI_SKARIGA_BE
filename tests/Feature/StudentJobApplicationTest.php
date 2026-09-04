<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApplicationStageHistory;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\SelectionStage;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
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
            ->assertJsonPath('data.data.0.vacancy.company_name', 'PT Teknologi Nusantara')
            ->assertJsonPath('data.data.0.status.code', 'pending')
            ->assertJsonPath('data.data.0.current_stage.name', 'Seleksi Berkas Administrasi')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'vacancy' => [
                                'id',
                                'title',
                                'company_name',
                                'company_logo',
                                'job_type',
                                'location',
                            ],
                            'status' => [
                                'id',
                                'name',
                                'code',
                            ],
                            'current_stage' => [
                                'id',
                                'name',
                            ],
                            'applied_at',
                            'notes',
                            'created_at',
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
            ->assertJsonPath('data.stage_histories.0.id', $history1->id)
            ->assertJsonPath('data.stage_histories.0.stage.name', 'Seleksi Berkas Administrasi')
            ->assertJsonPath('data.stage_histories.0.stage.order', 1)
            ->assertJsonPath('data.stage_histories.0.status.code', 'passed')
            ->assertJsonPath('data.stage_histories.0.assessor_name', 'HRD Penguji')
            ->assertJsonPath('data.stage_histories.0.score', 88.5)
            ->assertJsonPath('data.stage_histories.0.notes', 'Berkas lengkap dan sesuai kriteria')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'vacancy',
                    'status',
                    'current_stage',
                    'applied_at',
                    'notes',
                    'stage_histories' => [
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
                            'assessor_name',
                            'score',
                            'notes',
                            'created_at',
                        ],
                    ],
                    'created_at',
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
}
