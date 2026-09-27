<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApplicationStageHistory;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPlacement;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\Notification;
use App\Models\RecruitmentAttendance;
use App\Models\SelectionResult;
use App\Models\SelectionStage;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\PlacementStatusStandardTypeSeeder;
use Database\Seeders\RecruitmentAttendanceStandardTypeSeeder;
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $hrdUserA;

    protected User $hrdUserB;

    protected Company $companyA;

    protected Company $companyB;

    protected Major $major;

    protected User $siswaUser;

    protected StudentAlumni $studentAlumni;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MajorSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            JobApplicationStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            PlacementStatusStandardTypeSeeder::class,
        ]);

        $this->adminUser = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->hrdUserA = User::factory()->create(['role' => 'hrd', 'is_active' => true]);
        $this->hrdUserB = User::factory()->create(['role' => 'hrd', 'is_active' => true]);

        $this->companyA = Company::create([
            'user_id' => $this->hrdUserA->id,
            'name' => 'PT Company A',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'user_id' => $this->hrdUserB->id,
            'name' => 'PT Company B',
            'is_active' => true,
        ]);

        $this->major = Major::where('code', 'RPL')->firstOrFail();

        $this->siswaUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        $this->studentAlumni = StudentAlumni::create([
            'user_id' => $this->siswaUser->id,
            'major_id' => $this->major->id,
            'nis' => '7654321',
            'is_active' => true,
        ]);
    }

    private function makeVacancy(Company $company, array $overrides = []): JobVacancy
    {
        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();

        return JobVacancy::create(array_merge([
            'company_id' => $company->id,
            'title' => 'Operator Produksi',
            'position' => 'Operator',
            'description' => 'Deskripsi lowongan',
            'quota' => 5,
            'status_id' => $publishedStatus->id,
            'is_active' => true,
        ], $overrides));
    }

    private function makeApplication(JobVacancy $vacancy, StudentAlumni $student): JobApplication
    {
        $status = StandardType::byCategory('job_application_status')->where('code', 'in_progress')->firstOrFail();

        return JobApplication::create([
            'job_vacancy_id' => $vacancy->id,
            'student_alumni_id' => $student->id,
            'status_id' => $status->id,
            'applied_at' => now(),
        ]);
    }

    public function test_vacancy_detail_hides_inactive_vacancy(): void
    {
        $vacancy = $this->makeVacancy($this->companyA, ['is_active' => false]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson("/api/job-vacancies/{$vacancy->id}")
            ->assertNotFound();
    }

    public function test_vacancy_detail_hides_expired_vacancy(): void
    {
        $vacancy = $this->makeVacancy($this->companyA, ['deadline' => now()->subDay()->toDateString()]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson("/api/job-vacancies/{$vacancy->id}")
            ->assertNotFound();
    }

    public function test_vacancy_detail_serves_published_active_vacancy(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson("/api/job-vacancies/{$vacancy->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_bulk_attendance_validation_skips_already_validated_rows(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);
        $stage = SelectionStage::create([
            'job_vacancy_id' => $vacancy->id,
            'name' => 'Psikotes',
            'sequence_order' => 1,
        ]);
        $stageStatus = StandardType::byCategory('application_stage_status')->where('code', 'scheduled')->firstOrFail();
        $stageHistory = ApplicationStageHistory::create([
            'job_application_id' => $application->id,
            'selection_stage_id' => $stage->id,
            'status_id' => $stageStatus->id,
        ]);
        $attendanceStatus = StandardType::byCategory('attendance_status')->where('code', 'present')->firstOrFail();
        $attendance = RecruitmentAttendance::create([
            'stage_history_id' => $stageHistory->id,
            'attendance_status_id' => $attendanceStatus->id,
            'validation_status' => 'verified',
            'validated_by' => $this->adminUser->id,
            'validated_at' => now(),
            'notes' => 'Keputusan awal.',
            'attended_at' => now(),
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson('/api/admin/attendances/bulk-validate', [
                'attendance_ids' => [encrypt((string) $attendance->id)],
                'validation_status' => 'rejected',
                'notes' => 'Upaya validasi ulang.',
            ])
            ->assertOk()
            ->assertJsonPath('data.affected', 0);

        $this->assertDatabaseHas('recruitment_attendances', [
            'id' => $attendance->id,
            'validation_status' => 'verified',
            'notes' => 'Keputusan awal.',
        ]);
    }

    public function test_published_selection_decision_cannot_be_changed(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);
        $acceptedStatus = StandardType::byCategory('job_application_status')->where('code', 'accepted')->firstOrFail();

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $application->update(['status_id' => $acceptedStatus->id]);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->patchJson("/api/hrd/selection-results/{$application->id}/decision", [
                'decision' => 'tidak_diterima',
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('selection_results', [
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status_id' => $acceptedStatus->id,
        ]);
    }

    public function test_draft_selection_decision_can_still_be_changed(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->patchJson("/api/hrd/selection-results/{$application->id}/decision", [
                'decision' => 'cadangan',
            ])
            ->assertOk();

        $this->assertDatabaseHas('selection_results', [
            'job_application_id' => $application->id,
            'decision' => 'cadangan',
        ]);
    }

    public function test_hrd_cannot_reference_another_company_application_in_placement(): void
    {
        $vacancyB = $this->makeVacancy($this->companyB);
        $applicationB = $this->makeApplication($vacancyB, $this->studentAlumni);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/job-placements', [
                'student_alumni_id' => $this->studentAlumni->id,
                'job_application_id' => $applicationB->id,
            ])
            ->assertStatus(422);
    }

    public function test_hrd_placement_write_is_scoped_to_own_company_application(): void
    {
        $vacancyB = $this->makeVacancy($this->companyB);
        $applicationB = $this->makeApplication($vacancyB, $this->studentAlumni);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/job-placements', [
                'student_alumni_id' => $this->studentAlumni->id,
                'job_application_id' => $applicationB->id,
                'position' => 'Injected Position',
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('students_alumni', [
            'id' => $this->studentAlumni->id,
            'current_position' => 'Injected Position',
        ]);
    }

    public function test_hrd_can_create_placement_for_own_company_application(): void
    {
        $vacancyA = $this->makeVacancy($this->companyA);
        $applicationA = $this->makeApplication($vacancyA, $this->studentAlumni);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/job-placements', [
                'student_alumni_id' => $this->studentAlumni->id,
                'job_application_id' => $applicationA->id,
                'position' => 'Operator Produksi',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('students_alumni', [
            'id' => $this->studentAlumni->id,
            'current_position' => 'Operator Produksi',
            'current_company_id' => $this->companyA->id,
        ]);
    }

    public function test_alumni_update_rejects_non_student_user_binding(): void
    {
        $alumni = StudentAlumni::create([
            'user_id' => null,
            'major_id' => $this->major->id,
            'nis' => '5554443',
            'graduation_year' => 2024,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/alumni/{$alumni->id}", [
                'user_id' => $this->adminUser->id,
            ])
            ->assertStatus(422);
    }

    public function test_alumni_update_accepts_student_user_binding(): void
    {
        $freshStudentUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);

        $alumni = StudentAlumni::create([
            'user_id' => null,
            'major_id' => $this->major->id,
            'nis' => '5554443',
            'graduation_year' => 2024,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/alumni/{$alumni->id}", [
                'user_id' => $freshStudentUser->id,
                'graduation_year' => 2024,
            ])
            ->assertOk();

        $this->assertDatabaseHas('students_alumni', [
            'id' => $alumni->id,
            'user_id' => $freshStudentUser->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $freshStudentUser->id,
            'role' => 'alumni',
        ]);
    }

    public function test_alumni_can_be_edited_again_after_user_binding(): void
    {
        $freshStudentUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);

        $alumni = StudentAlumni::create([
            'user_id' => null,
            'major_id' => $this->major->id,
            'nis' => '7776665',
            'graduation_year' => 2024,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/alumni/{$alumni->id}", [
                'user_id' => $freshStudentUser->id,
                'graduation_year' => 2024,
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $freshStudentUser->id,
            'role' => 'alumni',
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/alumni/{$alumni->id}", [
                'user_id' => $freshStudentUser->id,
                'graduation_year' => 2024,
                'current_position' => 'Staff Produksi',
            ])
            ->assertOk();

        $this->assertDatabaseHas('students_alumni', [
            'id' => $alumni->id,
            'user_id' => $freshStudentUser->id,
            'current_position' => 'Staff Produksi',
        ]);
    }

    public function test_alumni_update_rejects_user_already_bound_elsewhere(): void
    {
        $boundUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        StudentAlumni::create([
            'user_id' => $boundUser->id,
            'major_id' => $this->major->id,
            'nis' => '1112223',
            'is_active' => true,
        ]);

        $target = StudentAlumni::create([
            'user_id' => null,
            'major_id' => $this->major->id,
            'nis' => '4445556',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/alumni/{$target->id}", [
                'user_id' => $boundUser->id,
            ])
            ->assertStatus(422);
    }

    public function test_published_at_is_set_on_publish(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', [
                'job_vacancy_id' => $vacancy->id,
            ])
            ->assertOk();

        $this->assertNotNull(
            SelectionResult::where('job_application_id', $application->id)->value('published_at')
        );
    }

    public function test_draft_after_publish_reopens_decision_for_editing(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', [
                'job_vacancy_id' => $vacancy->id,
            ])
            ->assertOk();

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->patchJson("/api/hrd/selection-results/{$application->id}/decision", [
                'decision' => 'tidak_diterima',
            ])
            ->assertStatus(422);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/draft', [
                'job_vacancy_id' => $vacancy->id,
            ])
            ->assertOk();

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->patchJson("/api/hrd/selection-results/{$application->id}/decision", [
                'decision' => 'tidak_diterima',
            ])
            ->assertOk();

        $this->assertDatabaseHas('selection_results', [
            'job_application_id' => $application->id,
            'decision' => 'tidak_diterima',
        ]);
    }

    public function test_hrd_cannot_relink_placement_to_other_student_application(): void
    {
        $vacancyA = $this->makeVacancy($this->companyA);
        $applicationA = $this->makeApplication($vacancyA, $this->studentAlumni);

        $otherStudentUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        $otherStudent = StudentAlumni::create([
            'user_id' => $otherStudentUser->id,
            'major_id' => $this->major->id,
            'nis' => '3334445',
            'is_active' => true,
        ]);
        $otherApplication = $this->makeApplication($vacancyA, $otherStudent);

        $placement = JobPlacement::create([
            'student_alumni_id' => $this->studentAlumni->id,
            'company_id' => $this->companyA->id,
            'placement_status_id' => $this->placementStatusId(),
            'created_by' => $this->hrdUserA->id,
        ]);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->patchJson("/api/hrd/job-placements/{$placement->id}", [
                'job_application_id' => $otherApplication->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('job_placements', [
            'id' => $placement->id,
            'student_alumni_id' => $this->studentAlumni->id,
            'job_application_id' => null,
        ]);
    }

    private function placementStatusId(): int
    {
        return StandardType::byCategory('placement_status')->value('id');
    }

    public function test_published_rows_without_published_at_are_still_locked_after_backfill(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'published',
            'published_at' => null,
        ]);

        DB::table('selection_results')
            ->where('status', 'published')
            ->whereNull('published_at')
            ->update(['published_at' => now()]);

        $this->assertNotNull(
            SelectionResult::where('job_application_id', $application->id)->value('published_at')
        );

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->patchJson("/api/hrd/selection-results/{$application->id}/decision", [
                'decision' => 'tidak_diterima',
            ])
            ->assertStatus(422);
    }

    public function test_publish_twice_without_draft_does_not_renotify(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $first = $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', ['job_vacancy_id' => $vacancy->id])
            ->assertOk();

        $second = $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', ['job_vacancy_id' => $vacancy->id])
            ->assertOk();

        $this->assertSame(1, $first->json('data.published_count'));
        $this->assertSame(0, $second->json('data.published_count'));
    }

    public function test_apply_is_refused_when_vacancy_has_null_status(): void
    {
        $vacancy = $this->makeVacancy($this->companyA, ['status_id' => null]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$vacancy->id}/apply")
            ->assertStatus(422);
    }

    public function test_draft_reopens_a_published_result_for_editing(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', ['job_vacancy_id' => $vacancy->id])
            ->assertOk();

        $this->assertNotNull(SelectionResult::where('job_application_id', $application->id)->value('published_at'));

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/draft', ['job_vacancy_id' => $vacancy->id])
            ->assertOk();

        $this->assertNull(SelectionResult::where('job_application_id', $application->id)->value('published_at'));

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->patchJson("/api/hrd/selection-results/{$application->id}/decision", [
                'decision' => 'cadangan',
            ])
            ->assertOk();
    }

    public function test_republish_after_draft_notifies_again(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', ['job_vacancy_id' => $vacancy->id])
            ->assertOk();

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/draft', ['job_vacancy_id' => $vacancy->id])
            ->assertOk();

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', ['job_vacancy_id' => $vacancy->id])
            ->assertOk()
            ->assertJsonPath('data.published_count', 1);
    }

    public function test_notification_mark_as_read_rejects_non_uuid(): void
    {
        $this->actingAs($this->siswaUser, 'sanctum')
            ->patchJson('/api/notification/not-a-uuid/read')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->patchJson('/api/notification/1/read')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Notifikasi tidak ditemukan.');

        $this->actingAs($this->siswaUser, 'sanctum')
            ->patchJson('/api/notification/'.str_repeat('a', 40).'/read')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Notifikasi tidak ditemukan.');
    }

    public function test_deactivating_company_revokes_hrd_tokens(): void
    {
        $this->hrdUserA->createToken('auth_token');

        $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/companies/{$this->companyA->id}/toggle-active")
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_vacancy_deadline_today_still_accepts_application(): void
    {
        $vacancy = $this->makeVacancy($this->companyA, ['deadline' => now()->toDateString()]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson("/api/job-vacancies/{$vacancy->id}")
            ->assertOk();

        $this->actingAs($this->siswaUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$vacancy->id}/apply")
            ->assertStatus(201);
    }

    public function test_student_does_not_see_unpublished_selection_decision(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson("/api/my-applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.selectionResult', null);
    }

    public function test_student_sees_published_selection_decision(): void
    {
        $vacancy = $this->makeVacancy($this->companyA);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson("/api/my-applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.selectionResult.decision', 'diterima');
    }

    public function test_publish_succeeds_for_vacancy_with_long_title(): void
    {
        $vacancy = $this->makeVacancy($this->companyA, [
            'title' => 'Lowongan Estimator & Quantity Surveyor Junior Divisi Infrastruktur Regional',
        ]);
        $application = $this->makeApplication($vacancy, $this->studentAlumni);

        SelectionResult::create([
            'job_application_id' => $application->id,
            'decision' => 'diterima',
            'status' => 'draft',
        ]);

        $this->actingAs($this->hrdUserA, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', ['job_vacancy_id' => $vacancy->id])
            ->assertOk()
            ->assertJsonPath('data.published_count', 1);

        $this->assertNotNull(
            SelectionResult::where('job_application_id', $application->id)->value('published_at')
        );
    }

    public function test_notification_title_is_truncated_to_column_limit(): void
    {
        $this->actingAs($this->siswaUser, 'sanctum');

        app(NotificationService::class)->send(
            $this->siswaUser->id,
            'test_long_title',
            str_repeat('Judul Sangat Panjang ', 10),
            'Pesan',
            [],
            false
        );

        $title = Notification::where('user_id', $this->siswaUser->id)
            ->where('type', 'test_long_title')
            ->value('title');

        $this->assertNotNull($title);
        $this->assertLessThanOrEqual(50, strlen($title));
    }

    public function test_notification_mark_as_read_accepts_uuid_param(): void
    {
        $notification = $this->siswaUser->notifications()->create([
            'type' => 'test',
            'title' => 'Judul',
            'message' => 'Pesan',
        ]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->patchJson("/api/notification/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_mark_as_read_accepts_uuid_param_in_production_env(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $notification = $this->siswaUser->notifications()->create([
            'type' => 'test',
            'title' => 'Judul Produksi',
            'message' => 'Pesan',
        ]);

        $this->actingAs($this->siswaUser, 'sanctum')
            ->patchJson("/api/notification/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
