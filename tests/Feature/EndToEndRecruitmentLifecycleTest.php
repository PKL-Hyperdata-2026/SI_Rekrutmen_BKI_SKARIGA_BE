<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\RecruitmentAttendance;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\TracerStudy;
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

class EndToEndRecruitmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $hrdUser;

    protected Company $company;

    protected User $studentUser;

    protected StudentAlumni $studentProfile;

    protected User $alumniUser;

    protected StudentAlumni $alumniProfile;

    protected Major $targetMajor;

    protected Major $otherMajor;

    protected StandardType $statusPending;

    protected StandardType $statusAccepted;

    protected StandardType $statusRejected;

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

        $this->statusPending = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();
        $this->statusAccepted = StandardType::byCategory('job_application_status')->where('code', 'accepted')->firstOrFail();
        $this->statusRejected = StandardType::byCategory('job_application_status')->where('code', 'rejected')->firstOrFail();

        $this->targetMajor = Major::firstOrFail();
        $this->otherMajor = Major::where('id', '!=', $this->targetMajor->id)->first() ?? Major::create([
            'department_id' => $this->targetMajor->department_id,
            'code' => 'OTH',
            'name' => 'Jurusan Lain',
            'is_active' => true,
        ]);

        // Admin User
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'full_name' => 'Admin BKK',
            'is_active' => true,
        ]);

        // HRD User & Company
        $this->hrdUser = User::factory()->create([
            'role' => 'hrd',
            'full_name' => 'HRD Mitra Industri',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'user_id' => $this->hrdUser->id,
            'name' => 'PT Solusi Teknologi Bersama',
            'is_active' => true,
            'email' => 'hrd@solusitek.test',
            'phone' => '081234567890',
            'address' => 'Kawasan Industri Banyuwangi',
        ]);

        // Siswa Kelas 12 Aktif
        $this->studentUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Siswa Pelamar Unggul',
            'is_active' => true,
        ]);

        $this->studentProfile = StudentAlumni::create([
            'user_id' => $this->studentUser->id,
            'major_id' => $this->targetMajor->id,
            'nis' => '123456',
            'graduation_year' => null,
            'is_active' => true,
        ]);

        // Alumni Lulusan
        $this->alumniUser = User::factory()->create([
            'role' => 'alumni',
            'full_name' => 'Alumni Sukses Berkarier',
            'is_active' => true,
        ]);

        $this->alumniProfile = StudentAlumni::create([
            'user_id' => $this->alumniUser->id,
            'major_id' => $this->targetMajor->id,
            'nis' => '654321',
            'graduation_year' => (int) date('Y') - 1,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Complete end-to-end recruitment lifecycle from job vacancy creation,
     * student application, administrative review, test scheduling, attendance validation,
     * scoring & published acceptance, job placement, alumni upgrade, and tracer study.
     */
    public function test_complete_happy_path_recruitment_lifecycle(): void
    {
        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->firstOrFail();
        $jobType = StandardType::byCategory('job_type')->first();

        // 1. HRD creates and publishes a Job Vacancy targeted to targetMajor
        $vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Junior Software Engineer',
            'slug' => 'junior-software-engineer-2026',
            'position' => 'Software Engineer',
            'job_type_id' => $jobType?->id,
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $targetBoth->id,
            'quota' => 5,
            'deadline' => now()->addDays(30),
            'work_location' => 'Banyuwangi',
            'is_active' => true,
            'created_by' => $this->hrdUser->id,
        ]);
        $vacancy->majors()->attach($this->targetMajor->id);

        // 2. Siswa explores vacancies and applies
        $applyResponse = $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$vacancy->id}/apply", [
                'notes' => 'Saya sangat tertarik dengan posisi Software Engineer.',
            ]);

        $applyResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $application = JobApplication::where('job_vacancy_id', $vacancy->id)
            ->where('student_alumni_id', $this->studentProfile->id)
            ->firstOrFail();

        $this->assertSame($this->statusPending->id, $application->status_id);

        // Siswa can view their application in my-applications
        $myAppResponse = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/my-applications/{$application->id}");

        $myAppResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.vacancy.position', 'Software Engineer');

        // 3. HRD reviews the applicant: passes administrative stage (lolos berkas)
        $reviewListResponse = $this->actingAs($this->hrdUser, 'sanctum')
            ->getJson('/api/hrd/applicant-reviews');

        $reviewListResponse->assertStatus(200)
            ->assertJsonPath('data.summary.perlu_review', 1);

        $reviewActionResponse = $this->actingAs($this->hrdUser, 'sanctum')
            ->patchJson("/api/hrd/applicant-reviews/{$application->id}/review", [
                'decision' => 'lolos',
                'notes' => 'Berkas lengkap dan sesuai kualifikasi.',
            ]);

        $reviewActionResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify selection result admin_selection_status is lolos and application is in_progress
        $application->refresh()->load('status');
        $this->assertSame('lolos', $application->selectionResult?->admin_selection_status);
        $this->assertSame('in_progress', $application->status?->code);

        // 4. HRD creates a test schedule and assigns the passed applicant
        $scheduleResponse = $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson('/api/hrd/test-schedules', [
                'job_vacancy_id' => $vacancy->id,
                'name' => 'Tes Psikotes & Wawancara Teknis',
                'description' => 'Membawa alat tulis dan kartu peserta.',
                'minimum_score' => 75.0,
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'scheduled_time' => '09:00',
                'location' => 'Lab Komputer 1 SMK PGRI 1 Giri',
                'application_ids' => [$application->id],
                'send_notification' => true,
            ]);

        $scheduleResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $scheduleId = (int) decrypt($scheduleResponse->json('data.id'));

        // Verify Attendance record generated for applicant
        $attendance = RecruitmentAttendance::whereHas('stageHistory', function ($q) use ($application, $scheduleId) {
            $q->where('job_application_id', $application->id)
                ->where('selection_stage_id', $scheduleId);
        })->firstOrFail();

        $this->assertSame('pending', $attendance->validation_status);

        // 5. Admin / Panitia validates attendance at the test
        $validateResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/attendances/{$attendance->id}/validate", [
                'validation_status' => 'verified',
                'notes' => 'Hadir tepat waktu dan mengisi presensi.',
            ]);

        $validateResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.validation.status', 'verified');

        $attendance->refresh();
        $this->assertSame('verified', $attendance->validation_status);

        // 6. HRD inputs test scores and publishes final decision (diterima)
        $submitScoresResponse = $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson("/api/hrd/selection-results/{$application->id}", [
                'psychotest_score' => 88.5,
                'interview_score' => 92.0,
                'mcu_score' => 90.0,
                'final_score' => 90.0,
                'decision' => 'diterima',
                'notes' => 'Kandidat sangat kompeten dan memenuhi standar industri.',
            ]);

        $submitScoresResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // HRD publishes results
        $publishResponse = $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', [
                'job_vacancy_id' => $vacancy->id,
            ]);

        $publishResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify application status updated to accepted
        $application->refresh()->load('status');
        $this->assertSame('accepted', $application->status?->code);
        $this->assertSame('published', $application->selectionResult?->status);
        $this->assertSame('diterima', $application->selectionResult?->decision);

        // Siswa checks portal: status is accepted!
        $studentPortalCheck = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/my-applications/{$application->id}");

        $studentPortalCheck->assertStatus(200)
            ->assertJsonPath('data.status.code', 'accepted')
            ->assertJsonPath('data.selectionResult.decision', 'diterima');

        // 7. HRD creates Job Placement for accepted student
        $placementStatus = StandardType::byCategory('placement_status')->first();

        $placementResponse = $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson('/api/hrd/job-placements', [
                'student_alumni_id' => $this->studentProfile->id,
                'company_id' => $this->company->id,
                'job_application_id' => $application->id,
                'placement_status_id' => $placementStatus?->id,
                'position' => 'Junior Software Engineer',
                'accepted_date' => now()->toDateString(),
                'start_date' => now()->addDays(14)->toDateString(),
                'notes' => 'Penempatan divisi backend engineering.',
            ]);

        $placementResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('job_placements', [
            'student_alumni_id' => $this->studentProfile->id,
            'company_id' => $this->company->id,
            'job_application_id' => $application->id,
        ]);

        // 8. Siswa completes studies, upgraded to alumni by Admin
        $upgradeResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/alumni', [
                'user_id' => $this->studentUser->id,
                'full_name' => $this->studentUser->full_name,
                'email' => $this->studentUser->email,
                'nis' => $this->studentProfile->nis,
                'major_id' => $this->targetMajor->id,
                'graduation_year' => (int) date('Y'),
                'is_active' => true,
            ]);

        $upgradeResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->studentUser->refresh();
        $this->assertSame('alumni', $this->studentUser->role);

        // 9. Now as an alumni, submit Tracer Study (Bekerja)
        $tracerResponse = $this->actingAs($this->studentUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'bekerja',
                'company_name' => 'PT Solusi Teknologi Bersama',
                'company_sector' => 'Teknologi Informasi',
                'job_title' => 'Junior Software Engineer',
                'job_location' => 'Banyuwangi',
                'minimum_salary' => 5000000,
                'maximum_salary' => 6500000,
                'waiting_period' => '1 Bulan',
                'job_alignment' => 'sangat_sesuai',
                'accepted_date' => now()->toDateString(),
                'start_date' => now()->addDays(14)->toDateString(),
            ]);

        $tracerResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.careerStatus', 'bekerja');

        // 10. Admin Dashboard reflects placed worker and active data
        $dashboardResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $dashboardResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $metrics = $dashboardResponse->json('data.metrics');
        $this->assertGreaterThanOrEqual(1, $metrics['placedWorkers']);
        $this->assertGreaterThanOrEqual(1, $metrics['totalAlumni']);
    }

    /**
     * Test 2: Rejection at administrative review stage prevents applicant from being scheduled for tests.
     */
    public function test_flow_rejection_at_administrative_stage_blocks_scheduling(): void
    {
        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->firstOrFail();

        $vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Teknisi Jaringan',
            'position' => 'Teknisi Jaringan',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $targetBoth->id,
            'quota' => 2,
            'is_active' => true,
        ]);
        $vacancy->majors()->attach($this->targetMajor->id);

        // Siswa applies
        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$vacancy->id}/apply")
            ->assertStatus(201);

        $application = JobApplication::where('job_vacancy_id', $vacancy->id)
            ->where('student_alumni_id', $this->studentProfile->id)
            ->firstOrFail();

        // HRD rejects applicant at administrative review
        $this->actingAs($this->hrdUser, 'sanctum')
            ->patchJson("/api/hrd/applicant-reviews/{$application->id}/review", [
                'decision' => 'tidak_lolos',
                'notes' => 'Kualifikasi nilai matematika di bawah standar.',
            ])
            ->assertStatus(200);

        $application->refresh()->load('status');
        $this->assertSame('rejected', $application->status?->code);
        $this->assertSame('tidak_lolos', $application->selectionResult?->admin_selection_status);

        // HRD creates test schedule specifying the rejected applicant
        $scheduleResponse = $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson('/api/hrd/test-schedules', [
                'job_vacancy_id' => $vacancy->id,
                'name' => 'Tes Kejuruan Jaringan',
                'minimum_score' => 70.0,
                'scheduled_date' => now()->addDays(2)->toDateString(),
                'scheduled_time' => '10:00',
                'location' => 'Ruang Teori 2',
                'application_ids' => [$application->id],
            ]);

        $scheduleResponse->assertStatus(201);
        $this->assertSame(0, $scheduleResponse->json('data.totalParticipants'));

        // Confirm no attendance record was created for the rejected applicant
        $attendanceExists = RecruitmentAttendance::whereHas('stageHistory', function ($q) use ($application) {
            $q->where('job_application_id', $application->id);
        })->exists();

        $this->assertFalse($attendanceExists);
    }

    /**
     * Test 3: Rejection at selection result stage properly marks application rejected
     * and reflects in student portal.
     */
    public function test_flow_rejection_at_selection_result_stage_blocks_placement(): void
    {
        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->firstOrFail();

        $vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Web Designer',
            'position' => 'Web Designer',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $targetBoth->id,
            'quota' => 1,
            'is_active' => true,
        ]);
        $vacancy->majors()->attach($this->targetMajor->id);

        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$vacancy->id}/apply")
            ->assertStatus(201);

        $application = JobApplication::where('job_vacancy_id', $vacancy->id)
            ->where('student_alumni_id', $this->studentProfile->id)
            ->firstOrFail();

        // Pass berkas
        $this->actingAs($this->hrdUser, 'sanctum')
            ->patchJson("/api/hrd/applicant-reviews/{$application->id}/review", [
                'decision' => 'lolos',
            ])->assertStatus(200);

        // Submit scores with decision: tidak_diterima
        $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson("/api/hrd/selection-results/{$application->id}", [
                'psychotest_score' => 50.0,
                'interview_score' => 55.0,
                'final_score' => 52.5,
                'decision' => 'tidak_diterima',
                'notes' => 'Skor di bawah batas kelulusan.',
            ])->assertStatus(200);

        // Publish results
        $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson('/api/hrd/selection-results/publish', [
                'job_vacancy_id' => $vacancy->id,
            ])->assertStatus(200);

        $application->refresh()->load('status');
        $this->assertSame('rejected', $application->status?->code);

        // Siswa checks portal: status is rejected
        $studentCheck = $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/my-applications/{$application->id}");

        $studentCheck->assertStatus(200)
            ->assertJsonPath('data.status.code', 'rejected')
            ->assertJsonPath('data.selectionResult.decision', 'tidak_diterima');
    }

    /**
     * Test 4: Validations block unauthorized, mismatched, or duplicate applications.
     */
    public function test_flow_ineligible_applications_are_blocked(): void
    {
        $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $closedStatus = StandardType::byCategory('vacancy_status')->where('code', 'closed')->firstOrFail();
        $class12Only = StandardType::byCategory('target_applicant')->where('code', 'class_12_only')->firstOrFail();
        $alumniOnly = StandardType::byCategory('target_applicant')->where('code', 'alumni_only')->firstOrFail();

        // 4a. Closed vacancy cannot be applied to
        $closedVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Closed Vacancy',
            'position' => 'Closed Position',
            'status_id' => $closedStatus->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$closedVacancy->id}/apply")
            ->assertStatus(422);

        // 4b. Expired vacancy cannot be applied to
        $expiredVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Expired Vacancy',
            'position' => 'Expired Position',
            'status_id' => $publishedStatus->id,
            'deadline' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$expiredVacancy->id}/apply")
            ->assertStatus(422);

        // 4c. Role restriction: Alumni applying to class_12_only vacancy
        $studentOnlyVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Siswa Magang Only',
            'position' => 'Intern',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $class12Only->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$studentOnlyVacancy->id}/apply")
            ->assertStatus(403);

        // 4d. Role restriction: Siswa applying to alumni_only vacancy
        $alumniOnlyVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Alumni Experienced Only',
            'position' => 'Senior Tech',
            'status_id' => $publishedStatus->id,
            'target_applicant_id' => $alumniOnly->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$alumniOnlyVacancy->id}/apply")
            ->assertStatus(403);

        // 4e. Major restriction: Candidate with otherMajor cannot apply
        $majorRestrictedVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Specific Major Only',
            'position' => 'Specialist',
            'status_id' => $publishedStatus->id,
            'is_active' => true,
        ]);
        $majorRestrictedVacancy->majors()->attach($this->otherMajor->id);

        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$majorRestrictedVacancy->id}/apply")
            ->assertStatus(403);

        // 4f. Duplicate application blocked
        $openVacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'General Open Vacancy',
            'position' => 'Staff',
            'status_id' => $publishedStatus->id,
            'is_active' => true,
        ]);
        $openVacancy->majors()->attach($this->targetMajor->id);

        // First application succeeds
        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$openVacancy->id}/apply")
            ->assertStatus(201);

        // Second application to same vacancy fails with 409 Conflict
        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/job-vacancies/{$openVacancy->id}/apply")
            ->assertStatus(409);
    }

    /**
     * Test 5: Tracer Study conditional field cleansing when alumni changes career status.
     */
    public function test_flow_tracer_study_conditional_data_cleanse(): void
    {
        // 1. Alumni submits tracer study as 'bekerja'
        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'bekerja',
                'company_name' => 'PT Lama Mandiri',
                'company_sector' => 'Keuangan',
                'job_title' => 'Accountant',
                'job_location' => 'Surabaya',
                'minimum_salary' => 4500000,
                'maximum_salary' => 5500000,
                'waiting_period' => '2 Bulan',
                'job_alignment' => 'sesuai',
                'start_date' => now()->subMonths(6)->toDateString(),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.careerStatus', 'bekerja')
            ->assertJsonPath('data.companyName', 'PT Lama Mandiri');

        $tracer = TracerStudy::where('student_alumni_id', $this->alumniProfile->id)->firstOrFail();
        $this->assertSame('PT Lama Mandiri', $tracer->company_name);
        $this->assertSame(4500000, $tracer->minimum_salary);

        // 2. Alumni updates tracer study to 'wirausaha'
        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'wirausaha',
                'business_name' => 'Kopi Sejahtera',
                'business_sector' => 'Kuliner',
                'business_field' => 'F&B Cafe',
                'monthly_revenue' => 10000000,
                'business_address' => 'Banyuwangi Kota',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.careerStatus', 'wirausaha')
            ->assertJsonPath('data.businessName', 'Kopi Sejahtera');

        $tracer->refresh();
        $this->assertSame('wirausaha', $tracer->career_status);
        $this->assertSame('Kopi Sejahtera', $tracer->business_name);

        // Crucial flow check: old 'bekerja' fields MUST be reset to null!
        $this->assertNull($tracer->company_name);
        $this->assertNull($tracer->company_sector);
        $this->assertNull($tracer->job_title);
        $this->assertNull($tracer->minimum_salary);
        $this->assertNull($tracer->maximum_salary);
    }
}
