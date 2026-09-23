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
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrdTestScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrdUserA;

    protected Company $companyA;

    protected User $hrdUserB;

    protected Company $companyB;

    protected User $siswaUser;

    protected StudentAlumni $studentA;

    protected StudentAlumni $studentB;

    protected StudentAlumni $studentC;

    protected JobVacancy $vacancyA;

    protected JobVacancy $vacancyB;

    protected StandardType $statusPending;

    protected StandardType $statusInProgress;

    protected StandardType $statusRejected;

    protected ?StandardType $stageTypePsikotes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            JobVacancyStandardTypeSeeder::class,
            MajorSeeder::class,
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
        ]);

        $this->statusPending = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();
        $this->statusInProgress = StandardType::byCategory('job_application_status')->where('code', 'in_progress')->firstOrFail();
        $this->statusRejected = StandardType::byCategory('job_application_status')->where('code', 'rejected')->firstOrFail();
        $this->stageTypePsikotes = StandardType::byCategory('stage_type')->where('code', 'psychological_test')->first();

        // HRD A & Company A
        $this->hrdUserA = User::factory()->create([
            'role' => 'hrd',
            'is_active' => true,
        ]);
        $this->companyA = Company::create([
            'user_id' => $this->hrdUserA->id,
            'name' => 'PT Astra SKARIGA',
            'is_active' => true,
            'email' => 'hrd@astra.com',
            'phone' => '08123456789',
        ]);

        // HRD B & Company B
        $this->hrdUserB = User::factory()->create([
            'role' => 'hrd',
            'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'user_id' => $this->hrdUserB->id,
            'name' => 'PT Telkom SKARIGA',
            'is_active' => true,
            'email' => 'hrd@telkom.com',
            'phone' => '08129876543',
        ]);

        // Students
        $major = Major::firstOrFail();

        $userStudentA = User::factory()->create(['role' => 'siswa', 'full_name' => 'Marvello Cikiwaw']);
        $this->studentA = StudentAlumni::create([
            'user_id' => $userStudentA->id,
            'major_id' => $major->id,
            'nis' => '25083',
            'gender' => 'L',
        ]);

        $userStudentB = User::factory()->create(['role' => 'siswa', 'full_name' => 'Windah Basuradar']);
        $this->studentB = StudentAlumni::create([
            'user_id' => $userStudentB->id,
            'major_id' => $major->id,
            'nis' => '25084',
            'gender' => 'L',
        ]);

        $userStudentC = User::factory()->create(['role' => 'siswa', 'full_name' => 'Aldi Taher']);
        $this->studentC = StudentAlumni::create([
            'user_id' => $userStudentC->id,
            'major_id' => $major->id,
            'nis' => '25085',
            'gender' => 'L',
        ]);

        // Vacancies
        $this->vacancyA = JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Junior Mechanic Operator',
            'position' => 'Junior Mechanic Operator',
            'slug' => 'junior-mechanic-operator',
            'quota' => 25,
            'is_active' => true,
        ]);

        $this->vacancyB = JobVacancy::create([
            'company_id' => $this->companyB->id,
            'title' => 'Network Engineer',
            'position' => 'Network Engineer',
            'slug' => 'network-engineer',
            'quota' => 10,
            'is_active' => true,
        ]);
    }

    public function test_hrd_can_fetch_form_options_including_stage_types_and_eligible_count(): void
    {
        // 1 passed applicant
        $app = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);
        SelectionResult::create([
            'job_application_id' => $app->id,
            'admin_selection_status' => 'lolos',
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->getJson('/api/hrd/test-schedules/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'vacancies',
                    'stage_types',
                ],
            ]);

        $vacancies = $response->json('data.vacancies');
        $this->assertNotEmpty($vacancies);
        $this->assertSame(1, $vacancies[0]['eligible_applicants_count']);

        $stageTypes = $response->json('data.stage_types');
        $this->assertNotEmpty($stageTypes);
    }

    public function test_hrd_can_create_test_schedule_with_stage_type_and_auto_allocates_passed_applicants(): void
    {
        // Applicant 1: Passed review (Lolos Berkas) via selectionResult
        $appPassed1 = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);
        SelectionResult::create([
            'job_application_id' => $appPassed1->id,
            'admin_selection_status' => 'lolos',
        ]);

        // Applicant 2: Passed review via status in_progress
        $appPassed2 = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        // Applicant 3: Rejected / Not passed review
        $appRejected = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentC->id,
            'status_id' => $this->statusRejected->id,
            'applied_at' => now(),
        ]);
        SelectionResult::create([
            'job_application_id' => $appRejected->id,
            'admin_selection_status' => 'tidak_lolos',
        ]);

        $payload = [
            'name' => 'Psikotes & Akademik - Batch 1',
            'job_vacancy_id' => $this->vacancyA->id,
            'stage_type_id' => $this->stageTypePsikotes?->id,
            'minimum_score' => 400.00,
            'scheduled_date' => now()->addDays(5)->format('Y-m-d'),
            'scheduled_time' => '08:00',
            'location' => 'Aula SKARIGA lt2',
            'description' => 'Membawa alat tulis dan kartu peserta.',
            'send_notification' => true,
        ];

        $response = $this->actingAs($this->hrdUserA)
            ->postJson('/api/hrd/test-schedules', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Psikotes & Akademik - Batch 1')
            ->assertJsonPath('data.totalParticipants', 2)
            ->assertJsonPath('data.minimumScore', 400)
            ->assertJsonPath('data.location', 'Aula SKARIGA lt2')
            ->assertJsonPath('data.sessionStatus', 'Siap Dilaksanakan')
            ->assertJsonPath('data.sessionStatusCode', 'ready')
            ->assertJsonPath('data.stageType.code', 'psychological_test');

        $this->assertNotNull($response->json('data.scheduledAtFormatted'));

        $stageId = decrypt($response->json('data.id'));

        // Assert histories created ONLY for the 2 passed applicants
        $this->assertDatabaseHas('application_stage_histories', [
            'selection_stage_id' => $stageId,
            'job_application_id' => $appPassed1->id,
        ]);
        $this->assertDatabaseHas('application_stage_histories', [
            'selection_stage_id' => $stageId,
            'job_application_id' => $appPassed2->id,
        ]);
        $this->assertDatabaseMissing('application_stage_histories', [
            'selection_stage_id' => $stageId,
            'job_application_id' => $appRejected->id,
        ]);

        // Assert current_stage_id updated
        $this->assertDatabaseHas('job_applications', [
            'id' => $appPassed1->id,
            'current_stage_id' => $stageId,
        ]);
    }

    public function test_hrd_rejects_invalid_time_format(): void
    {
        $payload = [
            'name' => 'Psikotes Batch Invalid',
            'job_vacancy_id' => $this->vacancyA->id,
            'minimum_score' => 70,
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => 'invalid-time',
            'location' => 'Room A',
        ];

        $response = $this->actingAs($this->hrdUserA)
            ->postJson('/api/hrd/test-schedules', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_time']);
    }

    public function test_hrd_can_create_test_schedule_with_specific_selected_applicant_ids(): void
    {
        // 2 applicants passed
        $app1 = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);
        $app2 = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        // Allocate only app1
        $payload = [
            'name' => 'Interview HRD - Klaster A',
            'job_vacancy_id' => $this->vacancyA->id,
            'minimum_score' => 75.50,
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '10:00',
            'location' => 'Zoom Meeting, ID: 882-123',
            'application_ids' => [$app1->id],
        ];

        $response = $this->actingAs($this->hrdUserA)
            ->postJson('/api/hrd/test-schedules', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.totalParticipants', 1);

        $stageId = decrypt($response->json('data.id'));

        $this->assertDatabaseHas('application_stage_histories', [
            'selection_stage_id' => $stageId,
            'job_application_id' => $app1->id,
        ]);
        $this->assertDatabaseMissing('application_stage_histories', [
            'selection_stage_id' => $stageId,
            'job_application_id' => $app2->id,
        ]);
    }

    public function test_hrd_cannot_create_schedule_for_another_company_vacancy(): void
    {
        $payload = [
            'name' => 'Illegal Schedule',
            'job_vacancy_id' => $this->vacancyB->id, // Belongs to Company B!
            'minimum_score' => 500,
            'scheduled_date' => now()->addDays(1)->format('Y-m-d'),
            'scheduled_time' => '09:00',
            'location' => 'Room 101',
        ];

        $response = $this->actingAs($this->hrdUserA)
            ->postJson('/api/hrd/test-schedules', $payload);

        $response->assertStatus(403);
    }

    public function test_hrd_can_list_filter_and_view_schedules(): void
    {
        $stageA = SelectionStage::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'stage_type_id' => $this->stageTypePsikotes?->id,
            'name' => 'Psikotes Batch 1',
            'scheduled_at' => now()->addDays(3),
            'location' => 'Lab Komputer',
            'minimum_score' => 70.00,
        ]);

        $stageB = SelectionStage::create([
            'job_vacancy_id' => $this->vacancyB->id,
            'name' => 'Psikotes Company B',
            'scheduled_at' => now()->addDays(3),
            'location' => 'Office B',
            'minimum_score' => 70.00,
        ]);

        // HRD A list
        $response = $this->actingAs($this->hrdUserA)
            ->getJson('/api/hrd/test-schedules');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data.data');
        $this->assertCount(1, $items);
        $this->assertEquals('Psikotes Batch 1', $items[0]['name']);
        $this->assertEquals('psychological_test', $items[0]['stageType']['code']);
        $this->assertEquals('ready', $items[0]['sessionStatusCode']);

        // View detail
        $detailResponse = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stageA->id}");

        $detailResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Psikotes Batch 1')
            ->assertJsonPath('data.location', 'Lab Komputer');

        // Cannot view other company's schedule
        $unauthorizedResponse = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stageB->id}");

        $unauthorizedResponse->assertStatus(404);
    }

    public function test_hrd_can_update_and_delete_schedule(): void
    {
        $stage = SelectionStage::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'name' => 'Initial Test',
            'scheduled_at' => now()->addDays(1),
            'location' => 'Hall SKARIGA',
            'minimum_score' => 60.00,
        ]);

        $updatePayload = [
            'name' => 'Updated Test Name',
            'location' => 'New Room 202',
            'minimum_score' => 80.00,
            'stage_type_id' => $this->stageTypePsikotes?->id,
        ];

        $response = $this->actingAs($this->hrdUserA)
            ->putJson("/api/hrd/test-schedules/{$stage->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Test Name')
            ->assertJsonPath('data.location', 'New Room 202')
            ->assertJsonPath('data.minimumScore', 80)
            ->assertJsonPath('data.stageType.code', 'psychological_test');

        // Delete
        $deleteResponse = $this->actingAs($this->hrdUserA)
            ->deleteJson("/api/hrd/test-schedules/{$stage->id}");

        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('selection_stages', ['id' => $stage->id]);
    }

    public function test_hrd_can_view_participants_and_send_individual_and_bulk_reminders(): void
    {
        $stage = SelectionStage::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'name' => 'Psikotes Akuntansi',
            'scheduled_at' => now()->addDays(2),
            'location' => 'Lab 1',
        ]);

        $appA = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        $appB = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        $historyA = ApplicationStageHistory::create([
            'job_application_id' => $appA->id,
            'selection_stage_id' => $stage->id,
        ]);

        $historyB = ApplicationStageHistory::create([
            'job_application_id' => $appB->id,
            'selection_stage_id' => $stage->id,
        ]);

        // Get participants
        $response = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stage->id}/participants");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'schedule',
                    'participants',
                ],
            ]);

        $participants = $response->json('data.participants.data');
        $this->assertCount(2, $participants);
        $this->assertEquals('Marvello Cikiwaw', $participants[1]['student']['name']);
        $this->assertEquals('25083', $participants[1]['student']['nis']);
        $this->assertEquals('Belum Presensi', $participants[1]['attendanceStatus']);
        $this->assertEquals('not_attended', $participants[1]['attendanceStatusCode']);

        // Schedule summary card assertions
        $this->assertEquals('Psikotes Akuntansi', $response->json('data.schedule.name'));
        $this->assertEquals('Junior Mechanic Operator', $response->json('data.schedule.jobVacancy.position'));

        // Send individual reminder
        $remindResponse = $this->actingAs($this->hrdUserA)
            ->postJson("/api/hrd/test-schedules/{$stage->id}/participants/{$historyA->id}/remind");

        $remindResponse->assertStatus(200)
            ->assertJsonPath('message', 'Pengingat jadwal tes berhasil dikirim ke peserta.');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentA->user_id,
            'type' => 'test_reminder',
        ]);

        // Send bulk reminder to all
        $bulkRemindResponse = $this->actingAs($this->hrdUserA)
            ->postJson("/api/hrd/test-schedules/{$stage->id}/remind-all");

        $bulkRemindResponse->assertStatus(200)
            ->assertJsonPath('data.reminded_count', 2);
    }

    public function test_hrd_cannot_access_or_remind_participants_of_another_company_schedule(): void
    {
        $stageA = SelectionStage::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'name' => 'Psikotes PT Astra',
            'scheduled_at' => now()->addDays(2),
            'location' => 'Lab Astra',
        ]);

        $appA = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        $historyA = ApplicationStageHistory::create([
            'job_application_id' => $appA->id,
            'selection_stage_id' => $stageA->id,
        ]);

        // HRD B (Telkom) tries to view participants of Astra's schedule -> 404
        $this->actingAs($this->hrdUserB)
            ->getJson("/api/hrd/test-schedules/{$stageA->id}/participants")
            ->assertStatus(404);

        // HRD B tries to remind participant of Astra's schedule -> 404
        $this->actingAs($this->hrdUserB)
            ->postJson("/api/hrd/test-schedules/{$stageA->id}/participants/{$historyA->id}/remind")
            ->assertStatus(404);

        // HRD B tries to bulk remind Astra's participants -> 404
        $this->actingAs($this->hrdUserB)
            ->postJson("/api/hrd/test-schedules/{$stageA->id}/remind-all")
            ->assertStatus(404);
    }

    public function test_filtering_and_searching_participants_by_attendance_status_and_keyword(): void
    {
        $stage = SelectionStage::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'name' => 'Tes Kejuruan',
            'scheduled_at' => now()->addDays(2),
            'location' => 'Workshop Otomotif',
        ]);

        $appA = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        $appB = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        $historyA = ApplicationStageHistory::create([
            'job_application_id' => $appA->id,
            'selection_stage_id' => $stage->id,
        ]);

        $historyB = ApplicationStageHistory::create([
            'job_application_id' => $appB->id,
            'selection_stage_id' => $stage->id,
        ]);

        // Student A has attended
        RecruitmentAttendance::create([
            'stage_history_id' => $historyA->id,
            'attended_at' => now(),
        ]);

        // Student B has NOT attended
        RecruitmentAttendance::create([
            'stage_history_id' => $historyB->id,
            'attended_at' => null,
        ]);

        // Filter by attendance_status = attended -> only student A
        $attendedResponse = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stage->id}/participants?attendance_status=attended");

        $attendedResponse->assertStatus(200);
        $this->assertCount(1, $attendedResponse->json('data.participants.data'));
        $this->assertEquals('Marvello Cikiwaw', $attendedResponse->json('data.participants.data.0.student.name'));

        // Filter by attendance_status = present (English synonym) -> only student A
        $presentResponse = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stage->id}/participants?attendance_status=present");

        $presentResponse->assertStatus(200);
        $this->assertCount(1, $presentResponse->json('data.participants.data'));
        $this->assertEquals('Marvello Cikiwaw', $presentResponse->json('data.participants.data.0.student.name'));

        // Filter by attendance_status = not_attended -> only student B
        $notAttendedResponse = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stage->id}/participants?attendance_status=not_attended");

        $notAttendedResponse->assertStatus(200);
        $this->assertCount(1, $notAttendedResponse->json('data.participants.data'));
        $this->assertEquals('Windah Basuradar', $notAttendedResponse->json('data.participants.data.0.student.name'));

        // Filter by attendance_status = absent (English synonym) -> only student B
        $absentResponse = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stage->id}/participants?attendance_status=absent");

        $absentResponse->assertStatus(200);
        $this->assertCount(1, $absentResponse->json('data.participants.data'));
        $this->assertEquals('Windah Basuradar', $absentResponse->json('data.participants.data.0.student.name'));

        // Rejection of Indonesian values (hadir / belum_presensi) with 422
        $indonesianResponse1 = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stage->id}/participants?attendance_status=hadir");
        $indonesianResponse1->assertStatus(422)
            ->assertJsonValidationErrors(['attendance_status']);

        $indonesianResponse2 = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stage->id}/participants?attendance_status=belum_presensi");
        $indonesianResponse2->assertStatus(422)
            ->assertJsonValidationErrors(['attendance_status']);

        // Search by keyword NIS 25083
        $searchResponse = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/test-schedules/{$stage->id}/participants?search=25083");

        $searchResponse->assertStatus(200);
        $this->assertCount(1, $searchResponse->json('data.participants.data'));
        $this->assertEquals('25083', $searchResponse->json('data.participants.data.0.student.nis'));
    }

    public function test_bulk_remind_only_targets_unattended_participants_and_skips_attended_ones(): void
    {
        $stage = SelectionStage::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'name' => 'Wawancara User',
            'scheduled_at' => now()->addDays(1),
            'location' => 'Meeting Room 1',
        ]);

        $appA = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        $appB = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusInProgress->id,
            'applied_at' => now(),
        ]);

        $historyA = ApplicationStageHistory::create([
            'job_application_id' => $appA->id,
            'selection_stage_id' => $stage->id,
        ]);

        $historyB = ApplicationStageHistory::create([
            'job_application_id' => $appB->id,
            'selection_stage_id' => $stage->id,
        ]);

        // Student A has attended
        RecruitmentAttendance::create([
            'stage_history_id' => $historyA->id,
            'attended_at' => now(),
        ]);

        // Student B has NOT attended
        RecruitmentAttendance::create([
            'stage_history_id' => $historyB->id,
            'attended_at' => null,
        ]);

        // Send bulk reminder -> should remind only student B (count = 1)
        $bulkResponse = $this->actingAs($this->hrdUserA)
            ->postJson("/api/hrd/test-schedules/{$stage->id}/remind-all");

        $bulkResponse->assertStatus(200)
            ->assertJsonPath('data.reminded_count', 1);

        // Student B received notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentB->user_id,
            'type' => 'test_reminder',
        ]);

        // Student A did NOT receive notification from bulk remind
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->studentA->user_id,
            'type' => 'test_reminder',
        ]);
    }
}
