<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApplicationStageHistory;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\RecruitmentAttendance;
use App\Models\SelectionStage;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\RecruitmentAttendanceStandardTypeSeeder;
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRecruitmentAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $studentUser;

    protected User $hrdUser;

    protected StudentAlumni $studentProfile;

    protected Company $company;

    protected JobVacancy $jobVacancy;

    protected SelectionStage $stage1;

    protected JobApplication $application;

    protected ApplicationStageHistory $stageHistory;

    protected RecruitmentAttendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
            MajorSeeder::class,
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'full_name' => 'Administrator BKI',
            'is_active' => true,
        ]);

        $this->studentUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Icha Chellow',
            'is_active' => true,
        ]);

        $this->hrdUser = User::factory()->create([
            'role' => 'hrd',
            'full_name' => 'HRD Tester',
            'is_active' => true,
        ]);

        $major = Major::firstOrFail();

        $this->studentProfile = StudentAlumni::create([
            'user_id' => $this->studentUser->id,
            'major_id' => $major->id,
            'nis' => '12345678',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'name' => 'PT Astra Honda Motor',
            'is_active' => true,
        ]);

        $this->jobVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Technician',
            'position' => 'Staff Teknisi',
            'is_active' => true,
        ]);

        $stageType = StandardType::byCategory('stage_type')->firstOrFail();

        $this->stage1 = SelectionStage::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'stage_type_id' => $stageType->id,
            'name' => 'Tes Psikotes',
            'sequence_order' => 1,
        ]);

        $appStatus = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();

        $this->application = JobApplication::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $appStatus->id,
            'current_stage_id' => $this->stage1->id,
            'applied_at' => now(),
        ]);

        $stageStatus = StandardType::byCategory('application_stage_status')->where('code', 'scheduled')->firstOrFail();

        $this->stageHistory = ApplicationStageHistory::create([
            'job_application_id' => $this->application->id,
            'selection_stage_id' => $this->stage1->id,
            'status_id' => $stageStatus->id,
        ]);

        $attendanceStatus = StandardType::byCategory('attendance_status')->where('code', 'present')->firstOrFail();

        $this->attendance = RecruitmentAttendance::create([
            'stage_history_id' => $this->stageHistory->id,
            'attendance_status_id' => $attendanceStatus->id,
            'validation_status' => 'pending',
            'attended_at' => now(),
        ]);
    }

    public function test_admin_can_get_vacancies_options(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/attendances/vacancies');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_name', 'PT Astra Honda Motor');

        $this->assertSame((string) $this->jobVacancy->id, decrypt($response->json('data.0.id')));
    }

    public function test_admin_can_get_stage_summaries(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/attendances/stage-summaries?job_vacancy_id={$this->jobVacancy->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', 'Semua Kategori')
            ->assertJsonPath('data.0.participantCount', 1)
            ->assertJsonPath('data.1.name', 'Tes Psikotes')
            ->assertJsonPath('data.1.participantCount', 1);
    }

    public function test_stage_summaries_validates_invalid_job_vacancy_id(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/attendances/stage-summaries?job_vacancy_id=999999');

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['job_vacancy_id']]);
    }

    public function test_admin_can_get_pending_attendance_queue(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/attendances/queue');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.applicant.name', 'Icha Chellow')
            ->assertJsonPath('data.data.0.vacancy.companyName', 'PT Astra Honda Motor')
            ->assertJsonPath('data.data.0.stage.name', 'Tes Psikotes')
            ->assertJsonPath('data.data.0.validation.status', 'pending');

        $this->assertSame((string) $this->attendance->id, decrypt($response->json('data.data.0.id')));
    }

    public function test_admin_can_validate_attendance_as_verified(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/attendances/{$this->attendance->id}/validate", [
                'validation_status' => 'verified',
                'notes' => 'Kehadiran dan lokasi valid.',
                'system_action' => 'Diteruskan ke HRD',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.validation.status', 'verified')
            ->assertJsonPath('data.validation.validatedByName', 'Administrator BKI')
            ->assertJsonPath('data.validation.notes', 'Kehadiran dan lokasi valid.');

        $this->assertDatabaseHas('recruitment_attendances', [
            'id' => $this->attendance->id,
            'validation_status' => 'verified',
            'validated_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_reject_attendance(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/attendances/{$this->attendance->id}/validate", [
                'validation_status' => 'rejected',
                'notes' => 'Lokasi anomali dan selfie blur.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.validation.status', 'rejected')
            ->assertJsonPath('data.validation.notes', 'Lokasi anomali dan selfie blur.')
            ->assertJsonPath('data.validation.systemAction', 'Gugur / Tidak Hadir');

        $this->assertDatabaseHas('recruitment_attendances', [
            'id' => $this->attendance->id,
            'validation_status' => 'rejected',
            'validated_by' => $this->adminUser->id,
            'notes' => 'Lokasi anomali dan selfie blur.',
        ]);
    }

    public function test_admin_cannot_revalidate_already_validated_attendance(): void
    {
        $this->attendance->update([
            'validation_status' => 'verified',
            'validated_by' => $this->adminUser->id,
            'validated_at' => now(),
            'notes' => 'Keputusan awal.',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/attendances/{$this->attendance->id}/validate", [
                'validation_status' => 'rejected',
                'notes' => 'Upaya validasi ulang.',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('recruitment_attendances', [
            'id' => $this->attendance->id,
            'validation_status' => 'verified',
            'notes' => 'Keputusan awal.',
        ]);
    }

    public function test_stage_summaries_group_duplicate_stage_names_across_vacancies(): void
    {
        $secondCompany = Company::create([
            'name' => 'PT Astra Otoparts',
            'is_active' => true,
        ]);

        $secondVacancy = JobVacancy::create([
            'company_id' => $secondCompany->id,
            'title' => 'Operator',
            'position' => 'Staff Operator',
            'is_active' => true,
        ]);

        $stageType = StandardType::byCategory('stage_type')->firstOrFail();

        $secondStage = SelectionStage::create([
            'job_vacancy_id' => $secondVacancy->id,
            'stage_type_id' => $stageType->id,
            'name' => 'tes psikotes',
            'sequence_order' => 1,
        ]);

        $appStatus = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();
        $stageStatus = StandardType::byCategory('application_stage_status')->where('code', 'scheduled')->firstOrFail();
        $attendanceStatus = StandardType::byCategory('attendance_status')->where('code', 'present')->firstOrFail();

        $secondApplication = JobApplication::create([
            'job_vacancy_id' => $secondVacancy->id,
            'student_alumni_id' => $this->studentProfile->id,
            'status_id' => $appStatus->id,
            'current_stage_id' => $secondStage->id,
            'applied_at' => now(),
        ]);

        $secondHistory = ApplicationStageHistory::create([
            'job_application_id' => $secondApplication->id,
            'selection_stage_id' => $secondStage->id,
            'status_id' => $stageStatus->id,
        ]);

        RecruitmentAttendance::create([
            'stage_history_id' => $secondHistory->id,
            'attendance_status_id' => $attendanceStatus->id,
            'validation_status' => 'pending',
            'attended_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/attendances/stage-summaries');

        $response->assertOk()->assertJsonPath('success', true);

        $entries = collect($response->json('data'))->where('name', 'Tes Psikotes')->values();
        $this->assertCount(1, $entries);
        $this->assertSame(2, $entries->first()['participantCount']);
        $decryptedStageIds = array_map(
            fn ($stageId) => (int) decrypt($stageId),
            $entries->first()['stageIds']
        );
        $this->assertEqualsCanonicalizing(
            [$this->stage1->id, $secondStage->id],
            $decryptedStageIds
        );
    }

    public function test_queue_can_filter_by_multiple_stage_ids(): void
    {
        $stageType = StandardType::byCategory('stage_type')->firstOrFail();

        $otherStage = SelectionStage::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'stage_type_id' => $stageType->id,
            'name' => 'Interview HR',
            'sequence_order' => 2,
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/attendances/queue?stage_ids[]={$otherStage->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.data');

        $queueResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/attendances/queue?stage_ids[]={$otherStage->id}&stage_ids[]={$this->stage1->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data');

        $this->assertSame((string) $this->attendance->id, decrypt($queueResponse->json('data.data.0.id')));
    }

    public function test_admin_can_get_validation_history(): void
    {
        $this->attendance->update([
            'validation_status' => 'verified',
            'validated_by' => $this->adminUser->id,
            'validated_at' => now(),
            'notes' => 'Valid',
            'system_action' => 'Diteruskan ke HRD',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/attendances/history');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.validation.status', 'verified');

        $this->assertSame((string) $this->attendance->id, decrypt($response->json('data.data.0.id')));
    }

    public function test_attendance_endpoints_accept_encrypted_identifiers(): void
    {
        $encryptedVacancyId = urlencode(encrypt((string) $this->jobVacancy->id));

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/attendances/queue?job_vacancy_id={$encryptedVacancyId}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data');

        $encryptedAttendanceId = urlencode(encrypt((string) $this->attendance->id));

        $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/attendances/{$encryptedAttendanceId}/validate", [
                'validation_status' => 'verified',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.validation.status', 'verified');

        $this->assertDatabaseHas('recruitment_attendances', [
            'id' => $this->attendance->id,
            'validation_status' => 'verified',
        ]);
    }

    public function test_non_admin_cannot_access_attendance_endpoints(): void
    {
        $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/admin/attendances/queue')
            ->assertForbidden();

        $this->actingAs($this->hrdUser, 'sanctum')
            ->getJson('/api/admin/attendances/queue')
            ->assertForbidden();

        $this->actingAs($this->studentUser, 'sanctum')
            ->patchJson("/api/admin/attendances/{$this->attendance->id}/validate", [
                'validation_status' => 'verified',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_bulk_validate_attendances(): void
    {
        $encryptedId = encrypt((string) $this->attendance->id);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson('/api/admin/attendances/bulk-validate', [
                'attendance_ids' => [$encryptedId],
                'validation_status' => 'verified',
                'notes' => 'Validasi massal kehadiran berhasil.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.affected', 1);

        $this->assertDatabaseHas('recruitment_attendances', [
            'id' => $this->attendance->id,
            'validation_status' => 'verified',
            'validated_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_bulk_reject_attendances(): void
    {
        $encryptedId = encrypt((string) $this->attendance->id);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson('/api/admin/attendances/bulk-validate', [
                'attendance_ids' => [$encryptedId],
                'validation_status' => 'rejected',
                'notes' => 'Penolakan massal kehadiran.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.affected', 1);

        $this->assertDatabaseHas('recruitment_attendances', [
            'id' => $this->attendance->id,
            'validation_status' => 'rejected',
            'validated_by' => $this->adminUser->id,
        ]);
    }
}
