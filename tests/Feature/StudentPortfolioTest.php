<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\StudentPortfolio;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\EmploymentStatusSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\StudentPortfolioStandartTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;

    protected StudentAlumni $student;

    protected Major $major;

    protected StandardType $class;

    protected StandardType $portfolioType;

    protected StandardType $employmentStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MajorSeeder::class,
            ClassSeeder::class,
            EmploymentStatusSeeder::class,
            StudentPortfolioStandartTypeSeeder::class,
        ]);

        $this->studentUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->major = Major::first();
        $this->class = StandardType::byCategory('class')->first();
        $this->portfolioType = StandardType::byCategory('portfolio_type')->first();
        $this->employmentStatus = StandardType::byCategory('employment_status')->first();

        $this->student = StudentAlumni::create([
            'user_id' => $this->studentUser->id,
            'major_id' => $this->major->id,
            'class_id' => $this->class->id,
            'nis' => '12345678',
            'is_active' => true,
        ]);
    }

    public function test_student_can_get_their_portfolio_profile(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/siswa/portfolio/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'userId' => $this->studentUser->id,
                    'nis' => '12345678',
                    'role' => 'siswa',
                ],
            ]);
    }

    public function test_student_can_get_form_options(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/siswa/portfolio/options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'majors',
                    'classes',
                    'employment_statuses',
                    'portfolio_types',
                    'graduation_years',
                ],
            ]);
    }

    public function test_student_can_update_their_profile(): void
    {
        $payload = [
            'nis' => '87654321',
            'full_name' => 'Marvello Cikiwaw Updated',
            'email' => 'marvello.updated@skariga.sch.id',
            'phone' => '081234567899',
            'class_id' => $this->class->id,
            'major_id' => $this->major->id,
            'graduation_year' => 2026,
            'social_media' => [
                ['platform' => 'linkedin', 'username' => 'marvello'],
                ['platform' => 'github', 'username' => 'marvellocikiwaw'],
            ],
        ];

        $response = $this->actingAs($this->studentUser)
            ->putJson('/api/siswa/portfolio/profile', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nis' => '87654321',
                    'fullName' => 'Marvello Cikiwaw Updated',
                    'email' => 'marvello.updated@skariga.sch.id',
                    'socialMedia' => [
                        ['platform' => 'linkedin', 'username' => 'marvello', 'url' => 'https://linkedin.com/in/marvello'],
                        ['platform' => 'github', 'username' => 'marvellocikiwaw', 'url' => 'https://github.com/marvellocikiwaw'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('students_alumni', [
            'id' => $this->student->id,
            'nis' => '87654321',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->studentUser->id,
            'email' => 'marvello.updated@skariga.sch.id',
        ]);
    }

    public function test_student_profile_normalizes_legacy_social_media_map(): void
    {
        $this->student->social_media = [
            'github' => 'https://github.com/legacyuser',
            'instagram' => 'https://instagram.com/@legacyuser',
        ];
        $this->student->save();

        $this->actingAs($this->studentUser)
            ->getJson('/api/siswa/portfolio/profile')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'socialMedia' => [
                        ['platform' => 'github', 'username' => 'legacyuser', 'url' => 'https://github.com/legacyuser'],
                        ['platform' => 'instagram', 'username' => '@legacyuser', 'url' => 'https://instagram.com/@legacyuser'],
                    ],
                ],
            ]);
    }

    public function test_student_can_upload_portfolio_document(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('cv_document.pdf', 500, 'application/pdf');

        $payload = [
            'category_id' => $this->portfolioType->id,
            'title' => 'Curriculum Vitae (CV)',
            'file' => $file,
        ];

        $response = $this->actingAs($this->studentUser)
            ->postJson('/api/siswa/portfolio/upload', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'categoryId' => $this->portfolioType->id,
                    'title' => 'Curriculum Vitae (CV)',
                ],
            ]);

        $portfolio = StudentPortfolio::where('student_alumni_id', $this->student->id)->first();
        $this->assertNotNull($portfolio);
        Storage::disk('public')->assertExists($portfolio->file_path);
    }

    public function test_student_can_delete_their_portfolio_document(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('sertifikat.pdf', 300, 'application/pdf');
        $path = $file->store('portfolios', 'public');

        $portfolio = StudentPortfolio::create([
            'student_alumni_id' => $this->student->id,
            'category_id' => $this->portfolioType->id,
            'title' => 'Sertifikat PKL',
            'file_path' => $path,
            'created_by' => $this->studentUser->id,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->deleteJson("/api/siswa/portfolio/{$portfolio->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('student_portfolios', [
            'id' => $portfolio->id,
        ]);
    }

    public function test_alumni_can_access_their_portfolio_profile(): void
    {
        $alumniUser = User::factory()->create([
            'role' => 'alumni',
            'is_active' => true,
        ]);

        $alumni = StudentAlumni::create([
            'user_id' => $alumniUser->id,
            'major_id' => $this->major->id,
            'class_id' => $this->class->id,
            'nis' => '99999999',
            'graduation_year' => 2024,
            'employment_status_id' => $this->employmentStatus->id,
            'is_active' => true,
        ]);

        $this->actingAs($alumniUser)
            ->getJson('/api/alumni/portfolio/profile')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'userId' => $alumniUser->id,
                    'nis' => '99999999',
                    'role' => 'alumni',
                    'employmentStatusId' => $this->employmentStatus->id,
                ],
            ]);
    }

    public function test_alumni_can_update_their_employment_status(): void
    {
        $alumniUser = User::factory()->create([
            'role' => 'alumni',
            'is_active' => true,
        ]);

        StudentAlumni::create([
            'user_id' => $alumniUser->id,
            'major_id' => $this->major->id,
            'class_id' => $this->class->id,
            'nis' => '88888888',
            'graduation_year' => 2024,
            'is_active' => true,
        ]);

        $targetStatus = StandardType::byCategory('employment_status')
            ->where('code', 'bekerja')
            ->first();

        $this->actingAs($alumniUser)
            ->putJson('/api/alumni/portfolio/profile', [
                'nis' => '88888888',
                'full_name' => 'Alumni Bekerja',
                'email' => $alumniUser->email,
                'phone' => '081222222222',
                'class_id' => $this->class->id,
                'major_id' => $this->major->id,
                'graduation_year' => 2024,
                'employment_status_id' => $targetStatus->id,
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'employmentStatusId' => $targetStatus->id,
                ],
            ]);

        $this->assertDatabaseHas('students_alumni', [
            'user_id' => $alumniUser->id,
            'employment_status_id' => $targetStatus->id,
        ]);
    }

    public function test_student_cannot_access_alumni_route(): void
    {
        $this->actingAs($this->studentUser)
            ->getJson('/api/alumni/portfolio/profile')
            ->assertStatus(403);
    }

    public function test_alumni_cannot_access_student_route(): void
    {
        $alumniUser = User::factory()->create([
            'role' => 'alumni',
            'is_active' => true,
        ]);

        StudentAlumni::create([
            'user_id' => $alumniUser->id,
            'major_id' => $this->major->id,
            'class_id' => $this->class->id,
            'nis' => '77777777',
            'graduation_year' => 2024,
            'is_active' => true,
        ]);

        $this->actingAs($alumniUser)
            ->getJson('/api/siswa/portfolio/profile')
            ->assertStatus(403);
    }
}
