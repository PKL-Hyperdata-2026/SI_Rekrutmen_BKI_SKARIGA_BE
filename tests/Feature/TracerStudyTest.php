<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Major;
use App\Models\StudentAlumni;
use App\Models\TracerStudy;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TracerStudyTest extends TestCase
{
    use RefreshDatabase;

    protected User $alumniUser;

    protected StudentAlumni $alumni;

    protected Major $major;

    protected User $siswaUser;

    protected User $hrdUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            DepartmentSeeder::class,
            MajorSeeder::class,
        ]);

        $this->major = Major::where('code', 'RPL')->firstOrFail();

        // Alumni utama untuk semua skenario happy path
        $this->alumniUser = User::factory()->create([
            'role' => 'alumni',
            'is_active' => true,
        ]);

        $this->alumni = StudentAlumni::create([
            'user_id' => $this->alumniUser->id,
            'major_id' => $this->major->id,
            'nis' => '212200001',
            'graduation_year' => 2024,
            'is_active' => true,
        ]);

        // User non-alumni untuk skenario 403
        $this->siswaUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        // Buat profil siswa agar konsisten (opsional, tidak dipakai TracerStudy)
        StudentAlumni::create([
            'user_id' => $this->siswaUser->id,
            'major_id' => $this->major->id,
            'nis' => '212200002',
            'graduation_year' => null,
            'is_active' => true,
        ]);

        $this->hrdUser = User::factory()->create([
            'role' => 'hrd',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    // -----------------------------------------------------------------
    // GET /api/alumni/tracer-study
    // -----------------------------------------------------------------

    public function test_alumni_can_get_empty_tracer_study_when_not_yet_submitted(): void
    {
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->getJson('/api/alumni/tracer-study');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Belum ada data tracer study yang diisi.');

        // ResponseService mengembalikan data null ketika belum ada record
        // toArray() tidak menyertakan key 'data' jika null, jadi cukup cek success & message
        // Jika implementasi menyertakan 'data' => null, kedua assertion di bawah tetap lolos
        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertEquals('Belum ada data tracer study yang diisi.', $json['message']);
    }

    public function test_alumni_can_get_existing_tracer_study(): void
    {
        $tracer = TracerStudy::create([
            'student_alumni_id' => $this->alumni->id,
            'career_status' => 'bekerja',
            'company_name' => 'PT Teknologi Nusantara',
            'job_title' => 'Software Engineer',
            'minimum_salary' => 5000000,
            'maximum_salary' => 7000000,
            'waiting_period' => '0-3 bulan',
            'start_date' => '2025-01-15',
            'created_by' => $this->alumniUser->id,
            'updated_by' => $this->alumniUser->id,
        ]);

        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->getJson('/api/alumni/tracer-study');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data tracer study berhasil diambil.')
            ->assertJsonPath('data.careerStatus', 'bekerja')
            ->assertJsonPath('data.companyName', 'PT Teknologi Nusantara')
            ->assertJsonPath('data.jobTitle', 'Software Engineer')
            ->assertJsonPath('data.studentAlumniId', $this->alumni->id)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'studentAlumniId',
                    'careerStatus',
                    'companyName',
                    'jobTitle',
                    'minimumSalary',
                    'maximumSalary',
                    'waitingPeriod',
                    'startDate',
                    'businessName',
                    'businessAddress',
                    'instagramAccount',
                    'averageRevenue',
                    'businessField',
                    'businessStartDate',
                    'universityName',
                    'studyProgram',
                    'createdAt',
                    'updatedAt',
                ],
            ]);
    }

    // -----------------------------------------------------------------
    // POST /api/alumni/tracer-study — status bekerja
    // -----------------------------------------------------------------

    public function test_alumni_can_submit_tracer_study_with_status_bekerja(): void
    {
        $payload = [
            'career_status' => 'bekerja',
            'company_name' => 'PT Teknologi Nusantara',
            'job_title' => 'Software Engineer',
            'minimum_salary' => 5000000,
            'maximum_salary' => 7000000,
            'waiting_period' => '0-3 bulan',
            'start_date' => '2025-01-15',
        ];

        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data tracer study berhasil disimpan.')
            ->assertJsonPath('data.careerStatus', 'bekerja')
            ->assertJsonPath('data.companyName', 'PT Teknologi Nusantara')
            ->assertJsonPath('data.jobTitle', 'Software Engineer')
            ->assertJsonPath('data.minimumSalary', 5000000)
            ->assertJsonPath('data.maximumSalary', 7000000)
            ->assertJsonPath('data.waitingPeriod', '0-3 bulan')
            ->assertJsonPath('data.startDate', '2025-01-15');

        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $this->alumni->id,
            'career_status' => 'bekerja',
            'company_name' => 'PT Teknologi Nusantara',
            'job_title' => 'Software Engineer',
            'created_by' => $this->alumniUser->id,
        ]);
    }

    // -----------------------------------------------------------------
    // POST — status wirausaha
    // -----------------------------------------------------------------

    public function test_alumni_can_submit_tracer_study_with_status_wirausaha(): void
    {
        $payload = [
            'career_status' => 'wirausaha',
            'business_name' => 'Toko Berkah Jaya',
            'business_address' => 'Jl. Melati No. 10 Jakarta Selatan',
            'business_field' => 'Kuliner',
            'instagram_handle' => '@tokoberkah',
            'average_income' => '5000000-10000000',
            'business_start_date' => '2024-06-01',
        ];

        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.careerStatus', 'wirausaha')
            ->assertJsonPath('data.businessName', 'Toko Berkah Jaya')
            ->assertJsonPath('data.businessAddress', 'Jl. Melati No. 10 Jakarta Selatan')
            ->assertJsonPath('data.businessField', 'Kuliner')
            ->assertJsonPath('data.instagramAccount', '@tokoberkah')
            ->assertJsonPath('data.businessStartDate', '2024-06-01');

        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $this->alumni->id,
            'career_status' => 'wirausaha',
            'business_name' => 'Toko Berkah Jaya',
            'business_field' => 'Kuliner',
        ]);

        // Kolom bekerja harus null (reset logic di Service)
        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $this->alumni->id,
            'company_name' => null,
            'job_title' => null,
        ]);
    }

    // -----------------------------------------------------------------
    // POST — status lanjut_studi
    // -----------------------------------------------------------------

    public function test_alumni_can_submit_tracer_study_with_status_lanjut_studi(): void
    {
        $payload = [
            'career_status' => 'lanjut_studi',
            'university_name' => 'Universitas Indonesia',
            'study_program' => 'Teknik Informatika',
        ];

        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.careerStatus', 'lanjut_studi')
            ->assertJsonPath('data.universityName', 'Universitas Indonesia')
            ->assertJsonPath('data.studyProgram', 'Teknik Informatika');

        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $this->alumni->id,
            'career_status' => 'lanjut_studi',
            'university_name' => 'Universitas Indonesia',
            'study_program' => 'Teknik Informatika',
        ]);

        // Kolom lain harus null
        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $this->alumni->id,
            'company_name' => null,
            'business_name' => null,
        ]);
    }

    // -----------------------------------------------------------------
    // POST — status mencari_pekerjaan
    // -----------------------------------------------------------------

    public function test_alumni_can_submit_tracer_study_with_status_mencari_pekerjaan(): void
    {
        $payload = [
            'career_status' => 'mencari_pekerjaan',
        ];

        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.careerStatus', 'mencari_pekerjaan')
            ->assertJsonPath('data.companyName', null)
            ->assertJsonPath('data.businessName', null)
            ->assertJsonPath('data.universityName', null);

        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $this->alumni->id,
            'career_status' => 'mencari_pekerjaan',
            'company_name' => null,
            'business_name' => null,
            'university_name' => null,
        ]);
    }

    // -----------------------------------------------------------------
    // Update: reset null data lama di dalam DB::transaction
    // -----------------------------------------------------------------

    public function test_submit_resets_previous_status_fields_when_changing_career_status(): void
    {
        // 1) Submit sebagai bekerja
        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'bekerja',
                'company_name' => 'PT Lama',
                'job_title' => 'Staff IT',
                'waiting_period' => '3-6 bulan',
                'start_date' => '2025-01-10',
                'minimum_salary' => 4000000,
                'maximum_salary' => 6000000,
            ])->assertCreated();

        // 2) Ganti menjadi wirausaha — field bekerja harus ter-reset null
        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'wirausaha',
                'business_name' => 'Usaha Baru',
                'business_address' => 'Jl. Baru No. 1',
                'business_field' => 'Teknologi',
            ])->assertCreated();

        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $this->alumni->id,
            'career_status' => 'wirausaha',
            'business_name' => 'Usaha Baru',
            // reset
            'company_name' => null,
            'job_title' => null,
            'minimum_salary' => null,
            'maximum_salary' => null,
            'waiting_period' => null,
            'start_date' => null,
        ]);

        // 3) Ganti lagi menjadi mencari_pekerjaan — semua detail harus null
        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'mencari_pekerjaan',
            ])->assertCreated();

        $this->assertDatabaseHas('tracer_studies', [
            'student_alumni_id' => $this->alumni->id,
            'career_status' => 'mencari_pekerjaan',
            'company_name' => null,
            'business_name' => null,
            'university_name' => null,
        ]);

        // Pastikan hanya 1 row per alumni (upsert, bukan duplikat)
        $this->assertEquals(1, TracerStudy::where('student_alumni_id', $this->alumni->id)->count());
    }

    // -----------------------------------------------------------------
    // Validasi
    // -----------------------------------------------------------------

    public function test_validation_fails_when_career_status_invalid(): void
    {
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['career_status']);
    }

    public function test_validation_fails_when_career_status_missing(): void
    {
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['career_status']);
    }

    public function test_validation_fails_when_required_fields_missing_for_bekerja(): void
    {
        // Hanya kirim career_status tanpa field wajib bekerja
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'bekerja',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'job_title', 'waiting_period', 'start_date']);
    }

    public function test_validation_fails_when_required_fields_missing_for_wirausaha(): void
    {
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'wirausaha',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['business_name', 'business_address', 'business_field']);
    }

    public function test_validation_fails_when_required_fields_missing_for_lanjut_studi(): void
    {
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'lanjut_studi',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['university_name', 'study_program']);
    }

    public function test_validation_fails_when_maximum_salary_less_than_minimum(): void
    {
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'bekerja',
                'company_name' => 'PT Test',
                'job_title' => 'QA Engineer',
                'waiting_period' => '0-3 bulan',
                'start_date' => '2025-02-01',
                'minimum_salary' => 7000000,
                'maximum_salary' => 5000000, // invalid: < minimum
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['maximum_salary']);
    }

    public function test_validation_fails_when_career_status_bekerja_with_invalid_date(): void
    {
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'bekerja',
                'company_name' => 'PT Test',
                'job_title' => 'QA Engineer',
                'waiting_period' => '0-3 bulan',
                'start_date' => 'not-a-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    // -----------------------------------------------------------------
    // RBAC — non-alumni ditolak 403
    // -----------------------------------------------------------------

    public function test_non_alumni_siswa_is_forbidden_on_get(): void
    {
        $this->actingAs($this->siswaUser, 'sanctum')
            ->getJson('/api/alumni/tracer-study')
            ->assertForbidden();
    }

    public function test_non_alumni_siswa_is_forbidden_on_post(): void
    {
        $this->actingAs($this->siswaUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'bekerja',
                'company_name' => 'PT Test',
                'job_title' => 'Staff',
                'waiting_period' => '0-3 bulan',
                'start_date' => '2025-01-01',
            ])->assertForbidden();
    }

    public function test_non_alumni_hrd_is_forbidden(): void
    {
        $this->actingAs($this->hrdUser, 'sanctum')
            ->getJson('/api/alumni/tracer-study')
            ->assertForbidden();

        $this->actingAs($this->hrdUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'mencari_pekerjaan',
            ])->assertForbidden();
    }

    public function test_non_alumni_admin_is_forbidden(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/alumni/tracer-study')
            ->assertForbidden();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/alumni/tracer-study', [
                'career_status' => 'mencari_pekerjaan',
            ])->assertForbidden();
    }

    public function test_unauthenticated_user_is_unauthorized(): void
    {
        $this->getJson('/api/alumni/tracer-study')
            ->assertUnauthorized();

        $this->postJson('/api/alumni/tracer-study', [
            'career_status' => 'bekerja',
            'company_name' => 'PT Test',
            'job_title' => 'Staff',
            'waiting_period' => '0-3 bulan',
            'start_date' => '2025-01-01',
        ])->assertUnauthorized();
    }
}
