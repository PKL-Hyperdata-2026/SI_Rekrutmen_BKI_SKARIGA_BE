<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPlacement;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\PlacementStatusStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJobPlacementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $siswaUser;

    protected Company $company;

    protected StudentAlumni $studentAlumni;

    protected StandardType $placementStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlacementStatusStandardTypeSeeder::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->siswaUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $major = Major::create([
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'name' => 'PT Astra Honda Motor',
            'is_active' => true,
            'address' => 'Kawasan Industri EJIP, Cikarang',
        ]);

        $this->studentAlumni = StudentAlumni::create([
            'user_id' => $this->siswaUser->id,
            'major_id' => $major->id,
            'nis' => '1234567',
            'graduation_year' => 2025,
            'is_active' => true,
        ]);

        $this->placementStatus = StandardType::byCategory('placement_status')->firstOrFail();
    }

    public function test_admin_can_list_job_placements(): void
    {
        JobPlacement::create([
            'student_alumni_id' => $this->studentAlumni->id,
            'company_id' => $this->company->id,
            'placement_status_id' => $this->placementStatus->id,
            'accepted_date' => '2026-08-01',
            'start_date' => '2026-08-15',
            'notes' => 'Penempatan divisi IT',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/job-placements');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'studentAlumniId',
                            'companyId',
                            'placementStatusId',
                            'acceptedDate',
                            'startDate',
                            'notes',
                        ],
                    ],
                ],
            ]);
    }

    public function test_admin_can_get_form_options(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/job-placements/options');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'companies',
                    'placement_statuses',
                    'students_alumni',
                ],
            ]);
    }

    public function test_admin_can_view_single_job_placement(): void
    {
        $placement = JobPlacement::create([
            'student_alumni_id' => $this->studentAlumni->id,
            'company_id' => $this->company->id,
            'placement_status_id' => $this->placementStatus->id,
            'accepted_date' => '2026-08-01',
            'start_date' => '2026-08-15',
            'notes' => 'Software Engineer Placement',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/job-placements/{$placement->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $placement->id)
            ->assertJsonPath('data.notes', 'Software Engineer Placement');
    }

    public function test_admin_can_create_job_placement(): void
    {
        $payload = [
            'student_alumni_id' => $this->studentAlumni->id,
            'company_id' => $this->company->id,
            'placement_status_id' => $this->placementStatus->id,
            'accepted_date' => '2026-08-10',
            'start_date' => '2026-09-01',
            'notes' => 'Junior Web Developer',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/job-placements', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notes', 'Junior Web Developer');

        $this->assertDatabaseHas('job_placements', [
            'student_alumni_id' => $this->studentAlumni->id,
            'company_id' => $this->company->id,
            'notes' => 'Junior Web Developer',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_validation_errors_when_required_fields_missing(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/job-placements', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_alumni_id', 'company_id']);
    }

    public function test_admin_can_update_job_placement(): void
    {
        $placement = JobPlacement::create([
            'student_alumni_id' => $this->studentAlumni->id,
            'company_id' => $this->company->id,
            'placement_status_id' => $this->placementStatus->id,
            'accepted_date' => '2026-08-01',
            'start_date' => '2026-08-15',
            'notes' => 'Initial Notes',
            'created_by' => $this->adminUser->id,
        ]);

        $updatePayload = [
            'notes' => 'Updated Notes after probation',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/job-placements/{$placement->id}", $updatePayload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notes', 'Updated Notes after probation');

        $this->assertDatabaseHas('job_placements', [
            'id' => $placement->id,
            'notes' => 'Updated Notes after probation',
            'updated_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_delete_job_placement(): void
    {
        $placement = JobPlacement::create([
            'student_alumni_id' => $this->studentAlumni->id,
            'company_id' => $this->company->id,
            'placement_status_id' => $this->placementStatus->id,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/admin/job-placements/{$placement->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('job_placements', [
            'id' => $placement->id,
            'deleted_by' => $this->adminUser->id,
        ]);
    }

    public function test_non_admin_cannot_access_job_placement_routes(): void
    {
        $response = $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson('/api/admin/job-placements');

        $response->assertForbidden();
    }
}
