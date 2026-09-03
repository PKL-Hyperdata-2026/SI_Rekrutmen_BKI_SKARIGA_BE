<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\User;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJobVacancyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $siswaUser;

    protected Company $company;

    protected StandardType $targetApplicant;

    protected StandardType $vacancyStatus;

    protected Major $rplMajor;

    protected Major $tkjMajor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(JobVacancyStandardTypeSeeder::class);
        $this->seed(MajorSeeder::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->siswaUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'name' => 'PT Astra Honda Motor',
            'is_active' => true,
            'address' => 'Kawasan Industri EJIP, Cikarang',
        ]);

        $this->targetApplicant = StandardType::byCategory('target_applicant')->where('code', 'alumni_only')->firstOrFail();
        $this->vacancyStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $this->rplMajor = Major::where('code', 'RPL')->firstOrFail();
        $this->tkjMajor = Major::where('code', 'TKJ')->firstOrFail();
    }

    public function test_can_fetch_form_options_including_all_majors(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/job-vacancies/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'companies',
                    'majors',
                    'vacancyStatuses',
                    'targetApplicants',
                    'jobTypes',
                ],
            ])
            ->assertJsonCount(15, 'data.majors');
    }

    public function test_admin_can_create_job_vacancy_with_select2_major_ids(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'position' => 'Software Engineer',
            'quota' => 5,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
            'major_ids' => [$this->rplMajor->id, $this->tkjMajor->id],
            'target_applicant_id' => $this->targetApplicant->id,
            'work_location' => 'Malang, Jawa Timur',
            'qualification' => "• Siswa aktif kelas 12 atau alumni\n• Menguasai dasar pemrograman",
            'description' => 'Bertanggung jawab dalam pengembangan sistem aplikasi.',
            'status_id' => $this->vacancyStatus->id,
            'send_notification' => false,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/job-vacancies', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.position', 'Software Engineer')
            ->assertJsonCount(2, 'data.majors')
            ->assertJsonPath('data.quota', 5)
            ->assertJsonPath('data.workLocation', 'Malang, Jawa Timur');

        $this->assertDatabaseHas('job_vacancies', [
            'position' => 'Software Engineer',
            'quota' => 5,
            'company_id' => $this->company->id,
        ]);

        $vacancyId = $response->json('data.id');
        $this->assertDatabaseHas('job_vacancy_majors', [
            'job_vacancy_id' => $vacancyId,
            'major_id' => $this->rplMajor->id,
        ]);
        $this->assertDatabaseHas('job_vacancy_majors', [
            'job_vacancy_id' => $vacancyId,
            'major_id' => $this->tkjMajor->id,
        ]);
    }

    public function test_non_admin_cannot_access_admin_job_vacancy_routes(): void
    {
        $response = $this->actingAs($this->siswaUser)
            ->getJson('/api/admin/job-vacancies');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_and_search_job_vacancies_by_major_and_keyword(): void
    {
        $vacancy1 = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Technician Operator',
            'position' => 'Technician Operator',
            'slug' => 'tech-op-1-abcde',
            'quota' => 25,
            'work_location' => 'Cikarang',
            'qualification' => 'Persyaratan lengkap',
            'target_applicant_id' => $this->targetApplicant->id,
            'status_id' => $this->vacancyStatus->id,
            'is_active' => true,
        ]);
        $vacancy1->majors()->sync([$this->tkjMajor->id]);

        $vacancy2 = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Junior Fullstack Developer',
            'position' => 'Junior Fullstack Developer',
            'slug' => 'dev-1-abcde',
            'quota' => 10,
            'work_location' => 'Surabaya',
            'qualification' => 'Persyaratan lengkap',
            'target_applicant_id' => $this->targetApplicant->id,
            'status_id' => $this->vacancyStatus->id,
            'is_active' => true,
        ]);
        $vacancy2->majors()->sync([$this->rplMajor->id]);

        // Search by position keyword
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/job-vacancies?search=Fullstack');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.position', 'Junior Fullstack Developer');

        // Filter by major_id
        $majorFilterResponse = $this->actingAs($this->adminUser)
            ->getJson("/api/admin/job-vacancies?major_id={$this->rplMajor->id}");

        $majorFilterResponse->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.position', 'Junior Fullstack Developer');
    }

    public function test_admin_can_fetch_detail_by_id_or_slug(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Technician Operator',
            'position' => 'Technician Operator',
            'slug' => 'tech-op-detail-test',
            'quota' => 25,
            'work_location' => 'Cikarang',
            'qualification' => 'Persyaratan detail',
            'target_applicant_id' => $this->targetApplicant->id,
            'status_id' => $this->vacancyStatus->id,
            'is_active' => true,
        ]);
        $vacancy->majors()->sync([$this->tkjMajor->id]);

        // Fetch by ID
        $responseById = $this->actingAs($this->adminUser)
            ->getJson("/api/admin/job-vacancies/{$vacancy->id}");

        $responseById->assertStatus(200)
            ->assertJsonPath('data.id', $vacancy->id)
            ->assertJsonCount(1, 'data.majors')
            ->assertJsonPath('data.majors.0.code', 'TKJ');

        // Fetch by Slug
        $responseBySlug = $this->actingAs($this->adminUser)
            ->getJson("/api/admin/job-vacancies/{$vacancy->slug}");

        $responseBySlug->assertStatus(200)
            ->assertJsonPath('data.slug', 'tech-op-detail-test');
    }

    public function test_admin_can_update_job_vacancy_with_major_ids(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Original Position',
            'position' => 'Original Position',
            'slug' => 'orig-pos-test',
            'quota' => 10,
            'work_location' => 'Jakarta',
            'qualification' => 'Original requirements',
            'target_applicant_id' => $this->targetApplicant->id,
            'status_id' => $this->vacancyStatus->id,
            'is_active' => true,
        ]);
        $vacancy->majors()->sync([$this->tkjMajor->id]);

        $updatePayload = [
            'position' => 'Senior Technician',
            'quota' => 50,
            'major_ids' => [$this->rplMajor->id, $this->tkjMajor->id],
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/admin/job-vacancies/{$vacancy->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.position', 'Senior Technician')
            ->assertJsonPath('data.quota', 50)
            ->assertJsonCount(2, 'data.majors');

        $this->assertDatabaseHas('job_vacancies', [
            'id' => $vacancy->id,
            'position' => 'Senior Technician',
            'quota' => 50,
        ]);

        $this->assertDatabaseHas('job_vacancy_majors', [
            'job_vacancy_id' => $vacancy->id,
            'major_id' => $this->rplMajor->id,
        ]);
    }

    public function test_admin_can_toggle_active_status(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Toggle Test',
            'position' => 'Toggle Test',
            'slug' => 'toggle-test',
            'quota' => 5,
            'work_location' => 'Malang',
            'qualification' => 'Toggle requirements',
            'target_applicant_id' => $this->targetApplicant->id,
            'status_id' => $this->vacancyStatus->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/admin/job-vacancies/{$vacancy->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('job_vacancies', [
            'id' => $vacancy->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_soft_delete_job_vacancy(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Delete Test',
            'position' => 'Delete Test',
            'slug' => 'delete-test',
            'quota' => 5,
            'work_location' => 'Malang',
            'qualification' => 'Delete requirements',
            'target_applicant_id' => $this->targetApplicant->id,
            'status_id' => $this->vacancyStatus->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/admin/job-vacancies/{$vacancy->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('job_vacancies', [
            'id' => $vacancy->id,
        ]);
    }
}
