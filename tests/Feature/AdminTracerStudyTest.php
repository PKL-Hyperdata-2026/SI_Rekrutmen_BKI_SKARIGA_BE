<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPlacement;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use App\Models\StudentAlumni;
use App\Models\TracerStudy;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTracerStudyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $siswaUser;
    protected User $hrdUser;
    protected Major $majorRpl;
    protected Major $majorDkv;
    protected StudentAlumni $alumni1;
    protected StudentAlumni $alumni2;
    protected TracerStudy $tracer1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            DepartmentSeeder::class,
            MajorSeeder::class,
        ]);

        $this->majorRpl = Major::where('code', 'RPL')->firstOrFail();
        $this->majorDkv = Major::where('code', 'DKV')->first() ?? Major::create(['code' => 'DKV', 'name' => 'Desain Komunikasi Visual', 'is_active' => true]);

        $this->adminUser = User::factory()->create([
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->siswaUser = User::factory()->create([
            'role'      => 'siswa',
            'is_active' => true,
        ]);

        $this->hrdUser = User::factory()->create([
            'role'      => 'hrd',
            'is_active' => true,
        ]);

        // Alumni 1: Windah Barusadar (Bekerja)
        $user1 = User::factory()->create([
            'full_name' => 'Windah Barusadar',
            'role'      => 'alumni',
            'is_active' => true,
        ]);

        $this->alumni1 = StudentAlumni::create([
            'user_id'         => $user1->id,
            'major_id'        => $this->majorRpl->id,
            'nis'             => '250491',
            'graduation_year' => 2026,
            'is_active'       => true,
        ]);

        $this->tracer1 = TracerStudy::create([
            'student_alumni_id' => $this->alumni1->id,
            'career_status'     => 'bekerja',
            'company_name'      => 'PT Hyperdata Indo',
            'company_sector'    => 'Sektor : Teknologi Komputer',
            'job_title'         => 'UI UX Design',
            'job_location'      => 'Malang, Jawa Timur',
            'minimum_salary'    => 4800000,
            'maximum_salary'    => 4800000,
            'waiting_period'    => '1 Bulan',
            'accepted_date'     => '2026-01-10',
            'start_date'        => '2026-02-19',
            'created_by'        => $this->adminUser->id,
        ]);

        // Alumni 2: Bambang Sugeh (Lanjut Studi)
        $user2 = User::factory()->create([
            'full_name' => 'Bambang Sugeh',
            'role'      => 'alumni',
            'is_active' => true,
        ]);

        $this->alumni2 = StudentAlumni::create([
            'user_id'         => $user2->id,
            'major_id'        => $this->majorDkv->id,
            'nis'             => '221002',
            'graduation_year' => 2025,
            'is_active'       => true,
        ]);

        TracerStudy::create([
            'student_alumni_id' => $this->alumni2->id,
            'career_status'     => 'lanjut_studi',
            'university_name'   => 'Universitas Brawijaya',
            'study_program'     => 'D4 Teknik Elektro',
            'job_location'      => 'Malang, Jatim',
            'created_by'        => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_list_tracer_studies_with_default_10_per_page(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.per_page', 10)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'studentAlumniId',
                            'careerStatus',
                            'companyName',
                            'jobTitle',
                            'jobLocation',
                            'acceptedDate',
                            'startDate',
                            'status12Bulan',
                            'studentAlumni' => [
                                'id',
                                'nis',
                                'fullName',
                                'major',
                            ],
                        ],
                    ],
                    'meta' => [
                        'total',
                        'current_page',
                        'per_page',
                    ],
                ],
            ]);
    }

    public function test_admin_can_search_tracer_studies(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies?search=Windah');

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.studentAlumni.fullName', 'Windah Barusadar');

        $responseCompany = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies?search=Brawijaya');

        $responseCompany->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.universityName', 'Universitas Brawijaya');
    }

    public function test_admin_can_filter_tracer_studies_by_career_status(): void
    {
        $responseBekerja = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies?career_status=bekerja');

        $responseBekerja->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.careerStatus', 'bekerja');

        $responseKuliah = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies?career_status=lanjut_studi');

        $responseKuliah->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.careerStatus', 'lanjut_studi');
    }

    public function test_admin_can_get_metrics(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies/metrics');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_alumni', 2)
            ->assertJsonPath('data.bekerja', 1)
            ->assertJsonPath('data.kuliah', 1)
            ->assertJsonPath('data.wirausaha', 0)
            ->assertJsonPath('data.mencari_kerja', 0);
    }

    public function test_admin_can_get_options(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies/options');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'majors',
                    'graduation_years',
                    'career_statuses',
                    'available_alumni',
                ],
            ]);
    }

    public function test_admin_can_show_tracer_study_detail(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies/' . $this->tracer1->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.careerStatus', 'bekerja')
            ->assertJsonPath('data.companyName', 'PT Hyperdata Indo')
            ->assertJsonPath('data.jobTitle', 'UI UX Design')
            ->assertJsonPath('data.status12Bulan', 'Masih Bekerja');
    }

    public function test_admin_can_create_tracer_study(): void
    {
        // Alumni 3 tanpa tracer study
        $user3 = User::factory()->create([
            'full_name' => 'Fikri Wirausaha',
            'role'      => 'alumni',
            'is_active' => true,
        ]);

        $alumni3 = StudentAlumni::create([
            'user_id'         => $user3->id,
            'major_id'        => $this->majorRpl->id,
            'nis'             => '253999',
            'graduation_year' => 2025,
            'is_active'       => true,
        ]);

        $payload = [
            'student_alumni_id'   => $alumni3->id,
            'career_status'       => 'wirausaha',
            'business_name'       => 'Kedai Kopi Skariga',
            'business_address'    => 'Malang',
            'business_field'      => 'Kuliner',
            'average_income'      => '5.000.000 - 10.000.000',
            'business_start_date' => '2025-05-01',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/tracer-studies', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.careerStatus', 'wirausaha')
            ->assertJsonPath('data.businessName', 'Kedai Kopi Skariga')
            ->assertJsonPath('data.status12Bulan', 'Wirausaha');

        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $alumni3->id,
            'career_status'     => 'wirausaha',
            'business_name'     => 'Kedai Kopi Skariga',
            'created_by'        => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_update_tracer_study_and_resets_other_fields(): void
    {
        $payload = [
            'career_status'   => 'lanjut_studi',
            'university_name' => 'Institut Teknologi Sepuluh Nopember',
            'study_program'   => 'S1 Informatika',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson('/api/admin/tracer-studies/' . $this->tracer1->id, $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.careerStatus', 'lanjut_studi')
            ->assertJsonPath('data.universityName', 'Institut Teknologi Sepuluh Nopember')
            ->assertJsonPath('data.companyName', null);

        $this->assertDatabaseHas('tracer_studies', [
            'id'              => $this->tracer1->id,
            'career_status'   => 'lanjut_studi',
            'university_name' => 'Institut Teknologi Sepuluh Nopember',
            'company_name'    => null,
            'job_title'       => null,
            'updated_by'      => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_soft_delete_tracer_study(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson('/api/admin/tracer-studies/' . $this->tracer1->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data tracer study berhasil dihapus.');

        $this->assertSoftDeleted('tracer_studies', [
            'id'         => $this->tracer1->id,
            'deleted_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_sync_tracer_study_from_job_placements(): void
    {
        // Alumni baru dengan penempatan kerja
        $userPlaced = User::factory()->create([
            'full_name' => 'Alumni Ditempatkan',
            'role'      => 'alumni',
            'is_active' => true,
        ]);

        $alumniPlaced = StudentAlumni::create([
            'user_id'            => $userPlaced->id,
            'major_id'           => $this->majorRpl->id,
            'nis'                => '259888',
            'graduation_year'    => 2026,
            'starting_salary'    => 5000000,
            'waiting_time_months'=> 1,
            'is_active'          => true,
        ]);

        $company = Company::factory()->create([
            'name'    => 'PT Astra Honda Motor',
            'address' => 'Karawang, Jabar',
        ]);

        JobPlacement::create([
            'student_alumni_id' => $alumniPlaced->id,
            'company_id'        => $company->id,
            'accepted_date'     => '2026-01-15',
            'start_date'        => '2026-02-01',
            'created_by'        => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/tracer-studies/sync');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.synced_count', 1);

        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $alumniPlaced->id,
            'career_status'     => 'bekerja',
            'company_name'      => 'PT Astra Honda Motor',
            'waiting_period'    => '1 Bulan',
        ]);
    }

    public function test_non_admin_cannot_access_admin_tracer_studies(): void
    {
        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies')
            ->assertForbidden();

        $this->actingAs($this->hrdUser, 'sanctum')
            ->getJson('/api/admin/tracer-studies')
            ->assertForbidden();
    }
}
