<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'admin',
    ]);
    $this->company = Company::create([
        'name' => 'PT Test Indonesia',
        'is_active' => true,
    ]);
    $category = StandardTypeCategory::firstOrCreate(
        ['code' => 'target_applicant'],
        ['name' => 'Target Pelamar']
    );
    $this->targetApplicant = StandardType::firstOrCreate(
        ['category_id' => $category->id, 'code' => 'class_12_and_alumni'],
        ['name' => 'Siswa Kelas 12 & Alumni', 'sort_order' => 3, 'is_active' => true]
    );
});

test('can create job vacancy with unique slug and majors', function () {
    $major = Major::create([
        'code' => 'RPL',
        'name' => 'Rekayasa Perangkat Lunak',
        'is_active' => true,
    ]);

    $payload = [
        'company_id' => $this->company->id,
        'target_applicant_id' => $this->targetApplicant->id,
        'title' => 'Junior Laravel Developer',
        'position' => 'Backend Developer',
        'description' => 'Job description details',
        'quota' => 2,
        'min_salary' => 5000000,
        'max_salary' => 8000000,
        'major_ids' => [$major->id],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/job-vacancies', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Junior Laravel Developer');

    $this->assertDatabaseHas('job_vacancies', [
        'title' => 'Junior Laravel Developer',
        'company_id' => $this->company->id,
    ]);

    $this->assertDatabaseHas('job_vacancy_majors', [
        'major_id' => $major->id,
    ]);
});

test('salary validation fails when max salary less than min salary', function () {
    $payload = [
        'company_id' => $this->company->id,
        'title' => 'Senior Developer',
        'min_salary' => 10000000,
        'max_salary' => 5000000, // Invalid: max < min
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/job-vacancies', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['max_salary']);
});

test('can support high salary up to billions', function () {
    $payload = [
        'company_id' => $this->company->id,
        'target_applicant_id' => $this->targetApplicant->id,
        'title' => 'VP of Engineering (Global)',
        'min_salary' => 1500000000.00,
        'max_salary' => 3000000000.00,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/job-vacancies', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.minSalary', '1500000000.00')
        ->assertJsonPath('data.maxSalary', '3000000000.00');
});

test('can fetch and update job vacancy', function () {
    $vacancy = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Initial Title',
        'slug' => 'initial-title-1-abcde',
    ]);

    $fetchResponse = $this->actingAs($this->user)
        ->getJson("/api/admin/job-vacancies/{$vacancy->id}");

    $fetchResponse->assertStatus(200)
        ->assertJsonPath('data.title', 'Initial Title');

    $updateResponse = $this->actingAs($this->user)
        ->putJson("/api/admin/job-vacancies/{$vacancy->id}", [
            'title' => 'Updated Title',
        ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.title', 'Updated Title');

    $this->assertDatabaseHas('job_vacancies', [
        'id' => $vacancy->id,
        'title' => 'Updated Title',
    ]);
});

test('scope active returns only active vacancies', function () {
    $activeVacancy = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Active Job',
        'slug' => 'active-job',
        'is_active' => true,
    ]);

    $inactiveVacancy = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Inactive Job',
        'slug' => 'inactive-job',
        'is_active' => false,
    ]);

    $activeVacancies = JobVacancy::active()->get();

    $this->assertTrue($activeVacancies->contains('id', $activeVacancy->id));
    $this->assertFalse($activeVacancies->contains('id', $inactiveVacancy->id));
});

test('scope open returns active vacancies with future or null deadlines', function () {
    $openWithNullDeadline = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Open No Deadline',
        'slug' => 'open-no-deadline',
        'is_active' => true,
        'deadline' => null,
    ]);

    $openWithFutureDeadline = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Open Future Deadline',
        'slug' => 'open-future-deadline',
        'is_active' => true,
        'deadline' => now()->addDays(7)->toDateString(),
    ]);

    $openWithTodayDeadline = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Open Today Deadline',
        'slug' => 'open-today-deadline',
        'is_active' => true,
        'deadline' => now()->toDateString(),
    ]);

    $pastDeadline = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Past Deadline',
        'slug' => 'past-deadline',
        'is_active' => true,
        'deadline' => now()->subDays(1)->toDateString(),
    ]);

    $inactiveWithFutureDeadline = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Inactive Future Deadline',
        'slug' => 'inactive-future-deadline',
        'is_active' => false,
        'deadline' => now()->addDays(7)->toDateString(),
    ]);

    $openVacancies = JobVacancy::open()->get();

    $this->assertTrue($openVacancies->contains('id', $openWithNullDeadline->id));
    $this->assertTrue($openVacancies->contains('id', $openWithFutureDeadline->id));
    $this->assertTrue($openVacancies->contains('id', $openWithTodayDeadline->id));
    $this->assertFalse($openVacancies->contains('id', $pastDeadline->id));
    $this->assertFalse($openVacancies->contains('id', $inactiveWithFutureDeadline->id));
});

test('scope expired returns vacancies with past deadlines', function () {
    $pastVacancy = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Past Vacancy',
        'slug' => 'past-vacancy',
        'deadline' => now()->subDays(2)->toDateString(),
    ]);

    $futureVacancy = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Future Vacancy',
        'slug' => 'future-vacancy',
        'deadline' => now()->addDays(2)->toDateString(),
    ]);

    $nullDeadlineVacancy = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'No Deadline Vacancy',
        'slug' => 'no-deadline-vacancy',
        'deadline' => null,
    ]);

    $todayVacancy = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Today Vacancy',
        'slug' => 'today-vacancy',
        'deadline' => now()->toDateString(),
    ]);

    $expiredVacancies = JobVacancy::expired()->get();

    $this->assertTrue($expiredVacancies->contains('id', $pastVacancy->id));
    $this->assertFalse($expiredVacancies->contains('id', $futureVacancy->id));
    $this->assertFalse($expiredVacancies->contains('id', $nullDeadlineVacancy->id));
    $this->assertFalse($expiredVacancies->contains('id', $todayVacancy->id));
});

test('job vacancy has applications and job applications relation', function () {
    $vacancy = JobVacancy::create([
        'company_id' => $this->company->id,
        'title' => 'Vacancy with Applications',
        'slug' => 'vacancy-apps',
    ]);

    $this->assertInstanceOf(HasMany::class, $vacancy->applications());
    $this->assertInstanceOf(HasMany::class, $vacancy->jobApplications());
});
