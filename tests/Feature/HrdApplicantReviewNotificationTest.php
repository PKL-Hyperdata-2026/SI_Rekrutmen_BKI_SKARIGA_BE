<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Notification;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrdApplicantReviewNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrdUser;

    protected Company $company;

    protected JobVacancy $vacancy;

    protected User $studentUser1;

    protected StudentAlumni $student1;

    protected User $studentUser2;

    protected StudentAlumni $student2;

    protected JobApplication $application1;

    protected JobApplication $application2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            JobVacancyStandardTypeSeeder::class,
            MajorSeeder::class,
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
        ]);

        $this->hrdUser = User::factory()->create(['role' => 'hrd', 'is_active' => true]);
        $this->company = Company::create([
            'user_id' => $this->hrdUser->id,
            'name' => 'PT Astra Honda Motor',
            'phone' => '081234567890',
            'email' => 'astra@honda.co.id',
            'address' => 'Jl. Pegangsaan Dua',
            'is_active' => true,
        ]);

        $statusPublished = StandardType::byCategory('vacancy_status')->where('code', 'published')->first();
        $targetAll = StandardType::byCategory('target_applicant')->where('code', 'all')->first();
        $jobType = StandardType::byCategory('job_type')->first();

        $this->vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Operator Produksi',
            'position' => 'Operator Produksi',
            'status_id' => $statusPublished?->id,
            'target_applicant_id' => $targetAll?->id,
            'job_type_id' => $jobType?->id,
            'quota' => 10,
            'deadline' => now()->addDays(30)->toDateString(),
            'is_active' => true,
            'created_by' => $this->hrdUser->id,
            'updated_by' => $this->hrdUser->id,
        ]);

        $this->studentUser1 = User::factory()->create(['role' => 'siswa', 'is_active' => true, 'full_name' => 'Ahmad Santoso']);
        $this->student1 = StudentAlumni::factory()->create(['user_id' => $this->studentUser1->id]);

        $this->studentUser2 = User::factory()->create(['role' => 'siswa', 'is_active' => true, 'full_name' => 'Bambang Pamungkas']);
        $this->student2 = StudentAlumni::factory()->create(['user_id' => $this->studentUser2->id]);

        $pendingStatus = StandardType::byCategory('job_application_status')->where('code', 'pending')->first();

        $this->application1 = JobApplication::create([
            'job_vacancy_id' => $this->vacancy->id,
            'student_alumni_id' => $this->student1->id,
            'status_id' => $pendingStatus?->id,
            'applied_at' => now(),
            'created_by' => $this->studentUser1->id,
            'updated_by' => $this->studentUser1->id,
        ]);

        $this->application2 = JobApplication::create([
            'job_vacancy_id' => $this->vacancy->id,
            'student_alumni_id' => $this->student2->id,
            'status_id' => $pendingStatus?->id,
            'applied_at' => now(),
            'created_by' => $this->studentUser2->id,
            'updated_by' => $this->studentUser2->id,
        ]);
    }

    public function test_reject_single_applicant_sends_notification(): void
    {
        $response = $this->actingAs($this->hrdUser)->patchJson(
            "/api/hrd/applicant-reviews/{$this->application1->id}/review",
            [
                'decision' => 'tidak_lolos',
                'notes' => 'Berkas belum memenuhi kualifikasi standar.',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentUser1->id,
            'type' => 'admin_review_failed',
        ]);

        $notification = Notification::where('user_id', $this->studentUser1->id)
            ->where('type', 'admin_review_failed')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Operator Produksi', $notification->title);
        $this->assertStringContainsString('PT Astra Honda Motor', $notification->message);
    }

    public function test_bulk_review_reject_sends_notification_to_all_rejected(): void
    {
        $response = $this->actingAs($this->hrdUser)->postJson(
            '/api/hrd/applicant-reviews/bulk-review',
            [
                'application_ids' => [$this->application1->id, $this->application2->id],
                'decision' => 'tidak_lolos',
                'notes' => 'Kualifikasi belum sesuai.',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentUser1->id,
            'type' => 'admin_review_failed',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentUser2->id,
            'type' => 'admin_review_failed',
        ]);
    }

    public function test_pass_single_applicant_does_not_send_fail_notification(): void
    {
        $response = $this->actingAs($this->hrdUser)->patchJson(
            "/api/hrd/applicant-reviews/{$this->application1->id}/review",
            [
                'decision' => 'lolos',
                'notes' => 'Berkas lengkap dan sesuai.',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->studentUser1->id,
            'type' => 'admin_review_failed',
        ]);
    }
}
