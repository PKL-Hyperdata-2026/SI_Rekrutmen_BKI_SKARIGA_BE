<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use App\Services\JobVacancyService;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

test('hrd can fetch form options and statistics', function () {
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
                'company' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                ],
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
        ->assertJsonPath('data.company.name', $this->companyA->name)
        ->assertJsonPath('data.company.email', $this->companyA->email)
        ->assertJsonPath('data.company.phone', $this->companyA->phone)
        ->assertJsonPath('data.statistics.activeCount', 1);

    $this->assertEquals($this->companyA->id, decrypt($response->json('data.company.id')));
});

test('hrd can fetch statistics endpoint', function () {
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
});

test('hrd can create job vacancy auto scoped to their company', function () {
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
});

test('hrd cannot create job vacancy with inactive major', function () {
    $inactiveMajor = Major::create([
        'department_id' => $this->rplMajor->department_id,
        'code' => 'INACTIVE',
        'name' => 'Inactive Major',
        'is_active' => false,
    ]);

    $payload = [
        'position' => 'Junior Mechanic Operator',
        'quota' => 25,
        'deadline' => now()->addDays(30)->format('Y-m-d'),
        'major_ids' => [$inactiveMajor->id],
        'target_applicant_id' => $this->targetApplicant->id,
        'work_location' => 'Plant Karawang',
        'qualification' => 'Persyaratan lengkap mekanik',
    ];

    $response = $this->actingAs($this->hrdUserA)
        ->postJson('/api/hrd/job-vacancies', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'major_ids.0' => 'Kategori jurusan yang dipilih tidak valid atau tidak aktif.',
        ]);
});

test('hrd can only list their own company vacancies', function () {
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
});

test('hrd can show detail by id and slug for own company', function () {
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
});

test('hrd cannot show detail of another company vacancy', function () {
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

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Anda tidak memiliki akses ke lowongan kerja perusahaan lain.');
});

test('hrd cannot show non existent vacancy', function () {
    $response = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies/non-existent-vacancy-id');

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Data lowongan kerja tidak ditemukan.');
});

test('hrd can update their own vacancy', function () {
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
});

test('hrd can update vacancy retaining expired deadline but cannot change to different past deadline', function () {
    $expiredDeadline = now()->subDays(5)->format('Y-m-d');
    $vacancy = JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Expired Position',
        'position' => 'Expired Position',
        'slug' => 'expired-pos',
        'quota' => 5,
        'deadline' => $expiredDeadline,
        'is_active' => true,
    ]);

    // Retaining the same expired deadline should pass validation (200 OK)
    $responseSame = $this->actingAs($this->hrdUserA)
        ->putJson("/api/hrd/job-vacancies/{$vacancy->id}", [
            'position' => 'Updated Expired Position',
            'deadline' => $expiredDeadline,
        ]);

    $responseSame->assertStatus(200)
        ->assertJsonPath('data.position', 'Updated Expired Position');

    // Changing to a different past deadline should fail validation with 422
    $differentPastDeadline = now()->subDays(10)->format('Y-m-d');
    $responseDifferent = $this->actingAs($this->hrdUserA)
        ->putJson("/api/hrd/job-vacancies/{$vacancy->id}", [
            'deadline' => $differentPastDeadline,
        ]);

    $responseDifferent->assertStatus(422)
        ->assertJsonValidationErrors(['deadline']);
    $this->assertEquals(
        'Batas pendaftaran tidak boleh di masa lalu.',
        $responseDifferent->json('errors.deadline.0')
    );
});

test('hrd cannot update job vacancy with inactive major', function () {
    $vacancy = JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Active Position',
        'position' => 'Active Position',
        'slug' => 'active-pos-major',
        'quota' => 5,
        'is_active' => true,
    ]);

    $inactiveMajor = Major::create([
        'department_id' => $this->rplMajor->department_id,
        'code' => 'INACTIVE2',
        'name' => 'Inactive Major 2',
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->hrdUserA)
        ->putJson("/api/hrd/job-vacancies/{$vacancy->id}", [
            'major_ids' => [$inactiveMajor->id],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'major_ids.0' => 'Kategori jurusan yang dipilih tidak valid atau tidak aktif.',
        ]);
});

test('hrd cannot update another company vacancy', function () {
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
});

test('hrd can toggle active status of their own vacancy', function () {
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
});

test('hrd cannot toggle another company vacancy', function () {
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
});

test('hrd can soft delete their own vacancy', function () {
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
});

test('hrd cannot delete another company vacancy', function () {
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
});

test('non hrd cannot access hrd routes', function () {
    $response = $this->actingAs($this->siswaUser)
        ->getJson('/api/hrd/job-vacancies');

    $response->assertStatus(403);
});

test('hrd without company profile is rejected', function () {
    $orphanHrd = User::factory()->create([
        'role' => 'hrd',
        'is_active' => true,
    ]);

    $response = $this->actingAs($orphanHrd)
        ->getJson('/api/hrd/job-vacancies');

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Akun HRD belum terhubung dengan data perusahaan.');
});

test('hrd without company cannot access options', function () {
    $orphanHrd = User::factory()->create([
        'role' => 'hrd',
        'is_active' => true,
    ]);

    $response = $this->actingAs($orphanHrd)
        ->getJson('/api/hrd/job-vacancies/options');

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Akun HRD belum terhubung dengan data perusahaan.');
});

test('job vacancy service hrd detail methods', function () {
    /** @var JobVacancyService $service */
    $service = app(JobVacancyService::class);

    $vacancyA = JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Service Pos A',
        'position' => 'Service Pos A',
        'slug' => 'service-pos-a',
        'quota' => 5,
        'is_active' => true,
    ]);

    // findJobVacancyDetail finds by id or slug
    $this->assertNotNull($service->findJobVacancyDetail((string) $vacancyA->id));
    $this->assertNotNull($service->findJobVacancyDetail('service-pos-a'));
    $this->assertNull($service->findJobVacancyDetail('non-existent-pos'));

    // getHrdVacancyDetail verifies company ownership
    $this->assertNotNull($service->getHrdVacancyDetail($this->companyA->id, (string) $vacancyA->id));
    $this->assertNotNull($service->getHrdVacancyDetail($this->companyA->id, 'service-pos-a'));
    $this->assertNull($service->getHrdVacancyDetail($this->companyB->id, (string) $vacancyA->id));
    $this->assertNull($service->getHrdVacancyDetail($this->companyA->id, 'non-existent-pos'));
});

test('hrd can filter vacancies by effective status', function () {
    JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Active Position',
        'position' => 'Active Position',
        'slug' => 'active-position',
        'quota' => 5,
        'deadline' => now()->addDays(30)->format('Y-m-d'),
        'is_active' => true,
    ]);

    // Still flagged active in DB but the deadline has passed.
    JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Expired Flagged Position',
        'position' => 'Expired Flagged Position',
        'slug' => 'expired-flagged-position',
        'quota' => 5,
        'deadline' => now()->subDays(5)->format('Y-m-d'),
        'is_active' => true,
    ]);

    JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Manual Closed Position',
        'position' => 'Manual Closed Position',
        'slug' => 'manual-closed-position',
        'quota' => 5,
        'deadline' => now()->addDays(30)->format('Y-m-d'),
        'is_active' => false,
    ]);

    $activeResponse = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies?effective_status=active');

    $activeResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.position', 'Active Position');

    $closedResponse = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies?effective_status=closed');

    $closedResponse->assertStatus(200)
        ->assertJsonCount(2, 'data.data')
        ->assertJsonPath('data.meta.total', 2);
});

test('hrd quota full filter ignores rejected applications', function () {
    $this->seed(JobApplicationStandardTypeSeeder::class);
    $pending = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();
    $rejected = StandardType::byCategory('job_application_status')->where('code', 'rejected')->firstOrFail();

    $studentUser = User::factory()->create(['role' => 'siswa']);
    $student = StudentAlumni::create([
        'user_id' => $studentUser->id,
        'major_id' => $this->rplMajor->id,
        'nis' => '99001',
        'nisn' => '9900000001',
        'gender' => 'L',
    ]);

    $fullVacancy = JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Full Position',
        'position' => 'Full Position',
        'slug' => 'full-position',
        'quota' => 1,
        'deadline' => now()->addDays(30)->format('Y-m-d'),
        'is_active' => true,
    ]);
    JobApplication::create([
        'job_vacancy_id' => $fullVacancy->id,
        'student_alumni_id' => $student->id,
        'status_id' => $pending->id,
        'applied_at' => now(),
    ]);

    // Only a rejected application: must NOT count as full.
    $rejectedOnlyVacancy = JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Rejected Only Position',
        'position' => 'Rejected Only Position',
        'slug' => 'rejected-only-position',
        'quota' => 2,
        'deadline' => now()->addDays(30)->format('Y-m-d'),
        'is_active' => true,
    ]);
    JobApplication::create([
        'job_vacancy_id' => $rejectedOnlyVacancy->id,
        'student_alumni_id' => $student->id,
        'status_id' => $rejected->id,
        'applied_at' => now(),
    ]);

    $fullResponse = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies?effective_status=quota_full');

    $fullResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.position', 'Full Position');

    $activeResponse = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies?effective_status=active');

    $activeResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.position', 'Rejected Only Position');
});

test('hrd can filter expiring and sort by deadline and quota', function () {
    $this->seed(JobApplicationStandardTypeSeeder::class);
    $pending = StandardType::byCategory('job_application_status')->where('code', 'pending')->firstOrFail();

    $studentUser = User::factory()->create(['role' => 'siswa']);
    $student = StudentAlumni::create([
        'user_id' => $studentUser->id,
        'major_id' => $this->rplMajor->id,
        'nis' => '99002',
        'nisn' => '9900000002',
        'gender' => 'L',
    ]);

    JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Far Deadline Position',
        'position' => 'Far Deadline Position',
        'slug' => 'far-deadline-position',
        'quota' => 10,
        'deadline' => now()->addDays(30)->format('Y-m-d'),
        'is_active' => true,
    ]);

    $soonVacancy = JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Soon Deadline Position',
        'position' => 'Soon Deadline Position',
        'slug' => 'soon-deadline-position',
        'quota' => 2,
        'deadline' => now()->addDays(3)->format('Y-m-d'),
        'is_active' => true,
    ]);
    JobApplication::create([
        'job_vacancy_id' => $soonVacancy->id,
        'student_alumni_id' => $student->id,
        'status_id' => $pending->id,
        'applied_at' => now(),
    ]);

    JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Old Expired Position',
        'position' => 'Old Expired Position',
        'slug' => 'old-expired-position',
        'quota' => 10,
        'deadline' => now()->subDays(10)->format('Y-m-d'),
        'is_active' => true,
    ]);

    $expiringResponse = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies?effective_status=expiring');

    $expiringResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.position', 'Soon Deadline Position');

    $deadlineSortResponse = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies?sort=deadline');

    $deadlineSortResponse->assertStatus(200)
        ->assertJsonPath('data.data.0.position', 'Soon Deadline Position')
        ->assertJsonPath('data.data.1.position', 'Far Deadline Position')
        ->assertJsonPath('data.data.2.position', 'Old Expired Position');

    $quotaSortResponse = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies?sort=quota');

    $quotaSortResponse->assertStatus(200)
        ->assertJsonPath('data.data.0.position', 'Soon Deadline Position')
        ->assertJsonPath('data.data.1.position', 'Old Expired Position')
        ->assertJsonPath('data.data.2.position', 'Far Deadline Position');
});

test('hrd ignores unknown effective status and sort', function () {
    JobVacancy::create([
        'company_id' => $this->companyA->id,
        'title' => 'Plain Position',
        'position' => 'Plain Position',
        'slug' => 'plain-position',
        'quota' => 5,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->hrdUserA)
        ->getJson('/api/hrd/job-vacancies?effective_status=bogus&sort=bogus');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.position', 'Plain Position');
});
