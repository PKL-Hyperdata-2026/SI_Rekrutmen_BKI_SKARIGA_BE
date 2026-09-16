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

class HrdJobVacancyTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrdUserA;

    protected Company $companyA;

    protected User $hrdUserB;

    protected Company $companyB;

    protected User $siswaUser;

    protected StandardType $targetApplicant;

    protected StandardType $vacancyStatusPublished;

    protected StandardType $vacancyStatusDraft;

    protected Major $rplMajor;

    protected Major $tkjMajor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(JobVacancyStandardTypeSeeder::class);
        $this->seed(MajorSeeder::class);

        // HRD A & Company A
        $this->hrdUserA = User::factory()->create([
            'role' => 'hrd',
            'is_active' => true,
        ]);

        $this->companyA = Company::create([
            'user_id' => $this->hrdUserA->id,
            'name' => 'PT Astra Honda Motor',
            'is_active' => true,
            'address' => 'Kawasan Industri EJIP, Cikarang',
            'email' => 'hrd@astra.co.id',
            'phone' => '021-898989',
        ]);

        // HRD B & Company B
        $this->hrdUserB = User::factory()->create([
            'role' => 'hrd',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'user_id' => $this->hrdUserB->id,
            'name' => 'PT Telkom Indonesia',
            'is_active' => true,
            'address' => 'Jl. Japati No. 1, Bandung',
            'email' => 'hrd@telkom.co.id',
            'phone' => '022-787878',
        ]);

        // Siswa User
        $this->siswaUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->targetApplicant = StandardType::byCategory('target_applicant')->whereIn('code', ['all', 'class_12_and_alumni'])->first() ?? StandardType::byCategory('target_applicant')->firstOrFail();
        $this->vacancyStatusPublished = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
        $this->vacancyStatusDraft = StandardType::byCategory('vacancy_status')->whereIn('code', ['draft', 'closed'])->first() ?? StandardType::byCategory('vacancy_status')->firstOrFail();
        $this->rplMajor = Major::where('code', 'RPL')->firstOrFail();
        $this->tkjMajor = Major::where('code', 'TKJ')->firstOrFail();
    }

    public function test_hrd_can_fetch_form_options_and_statistics(): void
    {
        // Seed 1 active vacancy for Company A
        JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Junior Operator',
            'position' => 'Junior Operator',
            'slug' => 'junior-op-a1',
            'quota' => 25,
            'target_applicant_id' => $this->targetApplicant->id,
            'status_id' => $this->vacancyStatusPublished->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->getJson('/api/hrd/job-vacancies/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'company',
                    'statistics' => [
                        'activeCount',
                        'draftOrClosedCount',
                        'totalCount',
                    ],
                    'majors',
                    'vacancyStatuses',
                    'targetApplicants',
                    'jobTypes',
                ],
            ])
            ->assertJsonPath('data.company.id', $this->companyA->id)
            ->assertJsonPath('data.statistics.activeCount', 1);
    }

    public function test_hrd_can_fetch_statistics_endpoint(): void
    {
        JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Operator',
            'position' => 'Operator',
            'slug' => 'op-a1',
            'quota' => 10,
            'status_id' => $this->vacancyStatusPublished->id,
            'is_active' => true,
        ]);

        JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Draft Position',
            'position' => 'Draft Position',
            'slug' => 'draft-a1',
            'quota' => 5,
            'status_id' => $this->vacancyStatusDraft->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->getJson('/api/hrd/job-vacancies/statistics');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.activeCount', 1)
            ->assertJsonPath('data.draftOrClosedCount', 1)
            ->assertJsonPath('data.totalCount', 2);
    }

    public function test_hrd_can_create_job_vacancy_auto_scoped_to_their_company(): void
    {
        $payload = [
            'position' => 'Junior Mechanic Operator',
            'quota' => 25,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
            'major_ids' => [$this->rplMajor->id, $this->tkjMajor->id],
            'target_applicant_id' => $this->targetApplicant->id,
            'work_location' => 'Plant Karawang',
            'qualification' => 'Persyaratan lengkap mekanik',
            'description' => 'Bertanggung jawab atas pemeliharaan mesin.',
            'status_id' => $this->vacancyStatusPublished->id,
            'send_notification' => false,
        ];

        $response = $this->actingAs($this->hrdUserA)
            ->postJson('/api/hrd/job-vacancies', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.position', 'Junior Mechanic Operator')
            ->assertJsonPath('data.quota', 25)
            ->assertJsonPath('data.workLocation', 'Plant Karawang')
            ->assertJsonCount(2, 'data.majors');
        $this->assertEquals($this->companyA->id, decrypt($response->json('data.companyId')));

        $this->assertDatabaseHas('job_vacancies', [
            'position' => 'Junior Mechanic Operator',
            'company_id' => $this->companyA->id,
            'quota' => 25,
        ]);
    }

    public function test_hrd_can_only_list_their_own_company_vacancies(): void
    {
        // Vacancy Company A
        JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Astra Technician',
            'position' => 'Astra Technician',
            'slug' => 'astra-tech-1',
            'quota' => 15,
            'is_active' => true,
        ]);

        // Vacancy Company B
        JobVacancy::create([
            'company_id' => $this->companyB->id,
            'title' => 'Telkom Network Engineer',
            'position' => 'Telkom Network Engineer',
            'slug' => 'telkom-net-1',
            'quota' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->getJson('/api/hrd/job-vacancies');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.position', 'Astra Technician');
        $this->assertEquals($this->companyA->id, decrypt($response->json('data.data.0.companyId')));
    }

    public function test_hrd_can_show_detail_by_id_and_slug_for_own_company(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Maintenance Technician Staff',
            'position' => 'Maintenance Technician Staff',
            'slug' => 'maint-tech-staff-slug',
            'quota' => 20,
            'work_location' => 'Head Office Jakarta',
            'is_active' => true,
        ]);

        // Show by ID
        $responseById = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/job-vacancies/{$vacancy->id}");

        $responseById->assertStatus(200)
            ->assertJsonPath('data.position', 'Maintenance Technician Staff');
        $this->assertEquals($vacancy->id, decrypt($responseById->json('data.id')));

        // Show by Slug
        $responseBySlug = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/job-vacancies/{$vacancy->slug}");

        $responseBySlug->assertStatus(200)
            ->assertJsonPath('data.slug', 'maint-tech-staff-slug');
    }

    public function test_hrd_cannot_show_detail_of_another_company_vacancy(): void
    {
        $vacancyB = JobVacancy::create([
            'company_id' => $this->companyB->id,
            'title' => 'Telkom Secret Position',
            'position' => 'Telkom Secret Position',
            'slug' => 'telkom-secret-pos',
            'quota' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->getJson("/api/hrd/job-vacancies/{$vacancyB->id}");

        $response->assertStatus(403);
    }

    public function test_hrd_can_update_their_own_vacancy(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Original Pos',
            'position' => 'Original Pos',
            'slug' => 'orig-pos-hrd',
            'quota' => 10,
            'is_active' => true,
        ]);

        $updatePayload = [
            'position' => 'Updated Quality Control Inspector',
            'quota' => 20,
            'major_ids' => [$this->rplMajor->id],
        ];

        $response = $this->actingAs($this->hrdUserA)
            ->putJson("/api/hrd/job-vacancies/{$vacancy->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.position', 'Updated Quality Control Inspector')
            ->assertJsonPath('data.quota', 20);

        $this->assertDatabaseHas('job_vacancies', [
            'id' => $vacancy->id,
            'position' => 'Updated Quality Control Inspector',
            'quota' => 20,
        ]);
    }

    public function test_hrd_cannot_update_another_company_vacancy(): void
    {
        $vacancyB = JobVacancy::create([
            'company_id' => $this->companyB->id,
            'title' => 'Telkom Position',
            'position' => 'Telkom Position',
            'slug' => 'telkom-pos-update',
            'quota' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->putJson("/api/hrd/job-vacancies/{$vacancyB->id}", [
                'position' => 'Hacked Position',
            ]);

        $response->assertStatus(403);
    }

    public function test_hrd_can_toggle_active_status_of_their_own_vacancy(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Toggle Pos',
            'position' => 'Toggle Pos',
            'slug' => 'toggle-pos-hrd',
            'quota' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->patchJson("/api/hrd/job-vacancies/{$vacancy->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('job_vacancies', [
            'id' => $vacancy->id,
            'is_active' => false,
        ]);
    }

    public function test_hrd_cannot_toggle_another_company_vacancy(): void
    {
        $vacancyB = JobVacancy::create([
            'company_id' => $this->companyB->id,
            'title' => 'Telkom Toggle',
            'position' => 'Telkom Toggle',
            'slug' => 'telkom-toggle',
            'quota' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->patchJson("/api/hrd/job-vacancies/{$vacancyB->id}/toggle-active");

        $response->assertStatus(403);
    }

    public function test_hrd_can_soft_delete_their_own_vacancy(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->companyA->id,
            'title' => 'Delete Pos',
            'position' => 'Delete Pos',
            'slug' => 'delete-pos-hrd',
            'quota' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->deleteJson("/api/hrd/job-vacancies/{$vacancy->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('job_vacancies', [
            'id' => $vacancy->id,
        ]);
    }

    public function test_hrd_cannot_delete_another_company_vacancy(): void
    {
        $vacancyB = JobVacancy::create([
            'company_id' => $this->companyB->id,
            'title' => 'Telkom Delete Pos',
            'position' => 'Telkom Delete Pos',
            'slug' => 'telkom-del-pos',
            'quota' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hrdUserA)
            ->deleteJson("/api/hrd/job-vacancies/{$vacancyB->id}");

        $response->assertStatus(403);
    }

    public function test_non_hrd_cannot_access_hrd_routes(): void
    {
        $response = $this->actingAs($this->siswaUser)
            ->getJson('/api/hrd/job-vacancies');

        $response->assertStatus(403);
    }

    public function test_hrd_without_company_profile_is_rejected(): void
    {
        $orphanHrd = User::factory()->create([
            'role' => 'hrd',
            'is_active' => true,
        ]);

        $response = $this->actingAs($orphanHrd)
            ->getJson('/api/hrd/job-vacancies');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Akun HRD belum terhubung dengan data perusahaan.');
    }
}
