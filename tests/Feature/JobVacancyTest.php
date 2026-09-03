<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobVacancyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'PT Test Indonesia',
            'is_active' => true,
        ]);
    }

    public function test_can_create_job_vacancy_with_unique_slug_and_majors(): void
    {
        $major = Major::create([
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $this->company->id,
            'title' => 'Junior Laravel Developer',
            'position' => 'Backend Developer',
            'description' => 'Job description details',
            'quota' => 2,
            'min_salary' => 5000000,
            'max_salary' => 8000000,
            'major_ids' => [$major->id],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/job-vacancies', $payload);

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
    }

    public function test_salary_validation_fails_when_max_salary_less_than_min_salary(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'title' => 'Senior Developer',
            'min_salary' => 10000000,
            'max_salary' => 5000000, // Invalid: max < min
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/job-vacancies', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_salary']);
    }

    public function test_can_support_high_salary_up_to_billions(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'title' => 'VP of Engineering (Global)',
            'min_salary' => 1500000000.00, // 1.5 Billion
            'max_salary' => 3000000000.00, // 3 Billion
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/job-vacancies', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.minSalary', '1500000000.00')
            ->assertJsonPath('data.maxSalary', '3000000000.00');
    }

    public function test_can_fetch_and_update_job_vacancy(): void
    {
        $vacancy = JobVacancy::create([
            'company_id' => $this->company->id,
            'title' => 'Initial Title',
            'slug' => 'initial-title-1-abcde',
        ]);

        $fetchResponse = $this->actingAs($this->user)
            ->getJson("/api/job-vacancies/{$vacancy->id}");

        $fetchResponse->assertStatus(200)
            ->assertJsonPath('data.title', 'Initial Title');

        $updateResponse = $this->actingAs($this->user)
            ->putJson("/api/job-vacancies/{$vacancy->id}", [
                'title' => 'Updated Title',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('job_vacancies', [
            'id' => $vacancy->id,
            'title' => 'Updated Title',
        ]);
    }
}
