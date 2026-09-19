<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\SelectionResult;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrdApplicantReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrdUserA;

    protected Company $companyA;

    protected User $hrdUserB;

    protected Company $companyB;

    protected JobVacancy $vacancyA;

    protected JobVacancy $vacancyB;

    protected StudentAlumni $studentA;

    protected StudentAlumni $studentB;

    protected StandardType $statusPending;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            JobVacancyStandardTypeSeeder::class,
            MajorSeeder::class,
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
        ]);

        $this->statusPending = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();

        $this->hrdUserA = User::factory()->create(['role' => 'hrd', 'is_active' => true]);
        $this->companyA = Company::create([
            'user_id' => $this->hrdUserA->id,
            'name' => 'PT Astra IND',
            'is_active' => true,
            'email' => 'hrd@astra.test',
            'phone' => '08123456789',
        ]);

        $this->hrdUserB = User::factory()->create(['role' => 'hrd', 'is_active' => true]);
        $this->companyB = Company::create([
            'user_id' => $this->hrdUserB->id,
            'name' => 'PT Telkom IND',
            'is_active' => true,
            'email' => 'hrd@telkom.test',
            'phone' => '08129876543',
        ]);

        $major = Major::firstOrFail();

        $userStudentA = User::factory()->create(['role' => 'siswa', 'full_name' => 'Aldi Taher Lucy']);
        $this->studentA = StudentAlumni::create([
            'user_id' => $userStudentA->id,
            'major_id' => $major->id,
            'nis' => '25073',
        ]);

        $userStudentB = User::factory()->create(['role' => 'siswa', 'full_name' => 'Kafka Bintang Love']);
        $this->studentB = StudentAlumni::create([
            'user_id' => $userStudentB->id,
            'major_id' => $major->id,
            'nis' => '22013',
        ]);

        $this->vacancyA = JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Front End Developer',
            'position' => 'Front End Developer',
            'slug' => 'front-end-developer',
            'quota' => 5,
            'is_active' => true,
        ]);

        $this->vacancyB = JobVacancy::create([
            'company_id' => $this->companyB->id,
            'title' => 'Network Engineer',
            'position' => 'Network Engineer',
            'slug' => 'network-engineer',
            'quota' => 3,
            'is_active' => true,
        ]);
    }

    public function test_hrd_without_company_gets_403(): void
    {
        $hrdNoCompany = User::factory()->create(['role' => 'hrd', 'is_active' => true]);

        $this->actingAs($hrdNoCompany)->getJson('/api/hrd/applicant-reviews')->assertStatus(403);
        $this->actingAs($hrdNoCompany)->getJson('/api/hrd/applicant-reviews/options')->assertStatus(403);
    }

    public function test_hrd_list_is_scoped_to_own_company(): void
    {
        JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);
        JobApplication::create([
            'job_vacancy_id' => $this->vacancyB->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($this->hrdUserA)->getJson('/api/hrd/applicant-reviews');

        $response->assertStatus(200)->assertJsonPath('success', true);
        $summary = $response->json('data.summary');
        $this->assertSame(1, $summary['total']);
        $this->assertSame(1, $summary['perlu_review']);

        $items = $response->json('data.applicants.data');
        $this->assertCount(1, $items);
        $this->assertSame('Aldi Taher Lucy', $items[0]['applicant']['name']);
        $this->assertSame('perlu_review', $items[0]['reviewStatus']);
        $this->assertFalse($items[0]['canScheduleTest']);
    }

    public function test_hrd_list_filter_review_status_and_search(): void
    {
        $appLolos = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);
        SelectionResult::create([
            'job_application_id' => $appLolos->id,
            'admin_selection_status' => 'lolos',
        ]);
        JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $filtered = $this->actingAs($this->hrdUserA)->getJson('/api/hrd/applicant-reviews?review_status=lolos_berkas');
        $filtered->assertStatus(200);
        $this->assertCount(1, $filtered->json('data.applicants.data'));
        $this->assertSame('lolos_berkas', $filtered->json('data.applicants.data.0.reviewStatus'));

        $searched = $this->actingAs($this->hrdUserA)->getJson('/api/hrd/applicant-reviews?search=Kafka');
        $searched->assertStatus(200);
        $this->assertCount(1, $searched->json('data.applicants.data'));
        $this->assertSame('Kafka Bintang Love', $searched->json('data.applicants.data.0.applicant.name'));
    }

    public function test_hrd_can_view_detail_and_options(): void
    {
        $app = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $detail = $this->actingAs($this->hrdUserA)->getJson("/api/hrd/applicant-reviews/{$app->id}");
        $detail->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSame('Front End Developer', $detail->json('data.vacancy.position'));
        $this->assertSame((string) decrypt($detail->json('data.id')), (string) $app->id);

        $options = $this->actingAs($this->hrdUserA)->getJson('/api/hrd/applicant-reviews/options');
        $options->assertStatus(200)->assertJsonPath('success', true);
        $this->assertCount(1, $options->json('data.vacancies'));
    }

    public function test_hrd_cannot_view_other_company_application(): void
    {
        $app = JobApplication::create([
            'job_vacancy_id' => $this->vacancyB->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $this->actingAs($this->hrdUserA)->getJson("/api/hrd/applicant-reviews/{$app->id}")->assertStatus(404);
    }

    public function test_hrd_can_review_applicant_lolos(): void
    {
        $app = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($this->hrdUserA)->patchJson(
            "/api/hrd/applicant-reviews/{$app->id}/review",
            ['decision' => 'lolos', 'notes' => 'Berkas lengkap.']
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Pelamar berhasil diloloskan pada seleksi administrasi.')
            ->assertJsonPath('data.reviewStatus', 'lolos_berkas');

        $this->assertDatabaseHas('selection_results', [
            'job_application_id' => $app->id,
            'admin_selection_status' => 'lolos',
        ]);

        $inProgress = StandardType::byCategory('job_application_status')->where('code', 'in_progress')->firstOrFail();
        $this->assertDatabaseHas('job_applications', [
            'id' => $app->id,
            'status_id' => $inProgress->id,
        ]);

        $passed = StandardType::byCategory('application_stage_status')->where('code', 'passed')->firstOrFail();
        $this->assertDatabaseHas('application_stage_histories', [
            'job_application_id' => $app->id,
            'status_id' => $passed->id,
            'assessor_id' => $this->hrdUserA->id,
        ]);
    }

    public function test_hrd_reject_without_notes_fails_validation(): void
    {
        $app = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($this->hrdUserA)->patchJson(
            "/api/hrd/applicant-reviews/{$app->id}/review",
            ['decision' => 'tidak_lolos']
        );

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseMissing('selection_results', ['job_application_id' => $app->id]);
    }

    public function test_hrd_can_review_applicant_tolak(): void
    {
        $app = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($this->hrdUserA)->patchJson(
            "/api/hrd/applicant-reviews/{$app->id}/review",
            ['decision' => 'tidak_lolos', 'notes' => 'Ijazah tidak dilampirkan.']
        );

        $response->assertStatus(200)->assertJsonPath('data.reviewStatus', 'ditolak');

        $rejected = StandardType::byCategory('job_application_status')->where('code', 'rejected')->firstOrFail();
        $this->assertDatabaseHas('job_applications', ['id' => $app->id, 'status_id' => $rejected->id]);

        $failed = StandardType::byCategory('application_stage_status')->where('code', 'failed')->firstOrFail();
        $this->assertDatabaseHas('application_stage_histories', [
            'job_application_id' => $app->id,
            'status_id' => $failed->id,
        ]);
    }

    public function test_hrd_bulk_review_lolos(): void
    {
        $appA = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentA->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);
        $appB = JobApplication::create([
            'job_vacancy_id' => $this->vacancyA->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($this->hrdUserA)->postJson('/api/hrd/applicant-reviews/bulk-review', [
            'application_ids' => [$appA->id, $appB->id],
            'decision' => 'lolos',
            'notes' => 'Berkas lengkap.',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSame(2, $response->json('data.succeeded'));
        $this->assertSame(0, $response->json('data.failed'));
        $this->assertDatabaseHas('selection_results', ['job_application_id' => $appA->id, 'admin_selection_status' => 'lolos']);
        $this->assertDatabaseHas('selection_results', ['job_application_id' => $appB->id, 'admin_selection_status' => 'lolos']);
    }

    public function test_hrd_cannot_review_other_company_application(): void
    {
        $app = JobApplication::create([
            'job_vacancy_id' => $this->vacancyB->id,
            'student_alumni_id' => $this->studentB->id,
            'status_id' => $this->statusPending->id,
            'applied_at' => now(),
        ]);

        $this->actingAs($this->hrdUserA)->patchJson(
            "/api/hrd/applicant-reviews/{$app->id}/review",
            ['decision' => 'lolos']
        )->assertStatus(404);

        $this->assertDatabaseMissing('selection_results', ['job_application_id' => $app->id]);
    }
}
