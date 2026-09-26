<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ApplicationStageHistoryStandardTypeSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\PlacementStatusStandardTypeSeeder;
use Database\Seeders\RecruitmentAttendanceStandardTypeSeeder;
use Database\Seeders\SelectionStageStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $siswaUser;

    protected User $hrdUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            PlacementStatusStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
            DepartmentSeeder::class,
            MajorSeeder::class,
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'full_name' => 'Admin BKI',
            'is_active' => true,
        ]);

        $this->siswaUser = User::factory()->create([
            'role' => 'siswa',
            'full_name' => 'Siswa Test',
            'is_active' => true,
        ]);

        $this->hrdUser = User::factory()->create([
            'role' => 'hrd',
            'full_name' => 'HRD Test',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertUnauthorized();
    }

    public function test_non_admin_role_cannot_access_admin_dashboard(): void
    {
        $responseSiswa = $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson('/api/admin/dashboard');
        $responseSiswa->assertForbidden();

        $responseHrd = $this->actingAs($this->hrdUser, 'sanctum')
            ->getJson('/api/admin/dashboard');
        $responseHrd->assertForbidden();
    }

    public function test_admin_can_access_dashboard_and_receive_valid_payload(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'metrics' => [
                        'activeStudents',
                        'totalAlumni',
                        'activeVacancies',
                        'applicantsThisMonth',
                        'placedWorkers',
                        'absorptionRate',
                    ],
                    'academicYearOptions',
                    'recruitmentChart' => [
                        '*' => [
                            'month',
                            'melamar',
                            'diterima',
                        ],
                    ],
                    'departmentDistribution' => [
                        '*' => [
                            'name',
                            'code',
                            'count',
                            'percentage',
                            'color',
                        ],
                    ],
                ],
            ]);
    }
}
