<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Department;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Database\Seeders\JobApplicationStandardTypeSeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        JobApplicationStandardTypeSeeder::class,
        JobVacancyStandardTypeSeeder::class,
        MajorSeeder::class,
    ]);

    $department = Department::firstOrCreate(
        ['code' => 'TIK'],
        ['name' => 'Teknologi Informasi dan Komunikasi', 'is_active' => true]
    );

    $this->majorRpl = Major::firstOrCreate(
        ['code' => 'RPL'],
        ['name' => 'Rekayasa Perangkat Lunak', 'department_id' => $department->id, 'is_active' => true]
    );

    $this->majorTkj = Major::firstOrCreate(
        ['code' => 'TKJ'],
        ['name' => 'Teknik Komputer dan Jaringan', 'department_id' => $department->id, 'is_active' => true]
    );

    $this->siswaUser = User::factory()->create([
        'role' => 'siswa',
        'full_name' => 'Siswa RPL',
        'is_active' => true,
    ]);

    $this->siswaProfile = StudentAlumni::create([
        'user_id' => $this->siswaUser->id,
        'major_id' => $this->majorRpl->id,
        'nis' => '10001',
        'is_active' => true,
    ]);

    $this->alumniUser = User::factory()->create([
        'role' => 'alumni',
        'full_name' => 'Alumni TKJ',
        'is_active' => true,
    ]);

    $this->alumniProfile = StudentAlumni::create([
        'user_id' => $this->alumniUser->id,
        'major_id' => $this->majorTkj->id,
        'graduation_year' => 2024,
        'nis' => '20002',
        'is_active' => true,
    ]);

    $this->company = Company::create([
        'name' => 'PT Mitra Solusi',
        'is_active' => true,
    ]);

    $this->targetSiswa = StandardType::byCategory('target_applicant')->where('code', 'class_12_only')->firstOrFail();
    $this->targetAlumni = StandardType::byCategory('target_applicant')->where('code', 'alumni_only')->firstOrFail();
    $this->targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->firstOrFail();
    $this->statusPublished = StandardType::byCategory('vacancy_status')->where('code', 'published')->firstOrFail();
});

test('siswa only sees vacancies for class 12 or both', function () {
    JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetSiswa->id,
        'title' => 'Lowongan Khusus Siswa',
        'position' => 'Staff',
        'is_active' => true,
    ]);

    JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetAlumni->id,
        'title' => 'Lowongan Khusus Alumni',
        'position' => 'Staff',
        'is_active' => true,
    ]);

    JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetBoth->id,
        'title' => 'Lowongan Siswa dan Alumni',
        'position' => 'Staff',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->siswaUser, 'sanctum')
        ->getJson('/api/job-vacancies');

    $response->assertOk();
    $titles = collect($response->json('data.data'))->pluck('title')->all();

    $this->assertContains('Lowongan Khusus Siswa', $titles);
    $this->assertContains('Lowongan Siswa dan Alumni', $titles);
    $this->assertNotContains('Lowongan Khusus Alumni', $titles);
});

test('alumni only sees vacancies for alumni or both', function () {
    JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetSiswa->id,
        'title' => 'Lowongan Khusus Siswa',
        'position' => 'Staff',
        'is_active' => true,
    ]);

    JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetAlumni->id,
        'title' => 'Lowongan Khusus Alumni',
        'position' => 'Staff',
        'is_active' => true,
    ]);

    JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetBoth->id,
        'title' => 'Lowongan Siswa dan Alumni',
        'position' => 'Staff',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->alumniUser, 'sanctum')
        ->getJson('/api/job-vacancies');

    $response->assertOk();
    $titles = collect($response->json('data.data'))->pluck('title')->all();

    $this->assertContains('Lowongan Khusus Alumni', $titles);
    $this->assertContains('Lowongan Siswa dan Alumni', $titles);
    $this->assertNotContains('Lowongan Khusus Siswa', $titles);
});

test('student sees vacancies matching major or open to all majors', function () {
    JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetBoth->id,
        'title' => 'Lowongan Semua Jurusan',
        'position' => 'Staff',
        'is_active' => true,
    ]);

    $vRpl = JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetBoth->id,
        'title' => 'Lowongan Khusus RPL',
        'position' => 'Programmer',
        'is_active' => true,
    ]);
    $vRpl->majors()->attach($this->majorRpl->id);

    $vTkj = JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetBoth->id,
        'title' => 'Lowongan Khusus TKJ',
        'position' => 'Network Engineer',
        'is_active' => true,
    ]);
    $vTkj->majors()->attach($this->majorTkj->id);

    $rplResponse = $this->actingAs($this->siswaUser, 'sanctum')
        ->getJson('/api/job-vacancies');

    $rplResponse->assertOk();
    $rplTitles = collect($rplResponse->json('data.data'))->pluck('title')->all();
    $this->assertContains('Lowongan Semua Jurusan', $rplTitles);
    $this->assertContains('Lowongan Khusus RPL', $rplTitles);
    $this->assertNotContains('Lowongan Khusus TKJ', $rplTitles);

    $tkjResponse = $this->actingAs($this->alumniUser, 'sanctum')
        ->getJson('/api/job-vacancies');

    $tkjResponse->assertOk();
    $tkjTitles = collect($tkjResponse->json('data.data'))->pluck('title')->all();
    $this->assertContains('Lowongan Semua Jurusan', $tkjTitles);
    $this->assertContains('Lowongan Khusus TKJ', $tkjTitles);
    $this->assertNotContains('Lowongan Khusus RPL', $tkjTitles);
});

test('filter options reflect role and major scope', function () {
    $vRpl = JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetSiswa->id,
        'title' => 'Lowongan Siswa RPL',
        'position' => 'Programmer',
        'is_active' => true,
    ]);
    $vRpl->majors()->attach($this->majorRpl->id);

    $vTkj = JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetAlumni->id,
        'title' => 'Lowongan Alumni TKJ',
        'position' => 'Network Engineer',
        'is_active' => true,
    ]);
    $vTkj->majors()->attach($this->majorTkj->id);

    $siswaOptions = $this->actingAs($this->siswaUser, 'sanctum')
        ->getJson('/api/job-vacancies/options');

    $siswaOptions->assertOk();
    $siswaTargets = collect($siswaOptions->json('data.targetApplicants'))->pluck('code')->all();
    $this->assertContains('class_12_only', $siswaTargets);
    $this->assertNotContains('alumni_only', $siswaTargets);

    $siswaMajors = collect($siswaOptions->json('data.majors'))->pluck('code')->all();
    $this->assertContains('RPL', $siswaMajors);
    $this->assertNotContains('TKJ', $siswaMajors);

    $firstTargetId = (string) $siswaOptions->json('data.targetApplicants.0.id');
    $this->assertFalse(is_numeric($firstTargetId));
    $this->assertEquals($this->targetSiswa->id, (int) decrypt($firstTargetId));

    $firstMajorId = (string) $siswaOptions->json('data.majors.0.id');
    $this->assertFalse(is_numeric($firstMajorId));
    $this->assertEquals($this->majorRpl->id, (int) decrypt($firstMajorId));
});

test('student cannot apply to vacancy of different major', function () {
    $vTkj = JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetBoth->id,
        'title' => 'Lowongan Khusus TKJ',
        'position' => 'Network Engineer',
        'is_active' => true,
    ]);
    $vTkj->majors()->attach($this->majorTkj->id);

    $response = $this->actingAs($this->siswaUser, 'sanctum')
        ->postJson("/api/job-vacancies/{$vTkj->id}/apply", [
            'notes' => 'Mencoba melamar jurusan berbeda',
        ]);

    $response->assertStatus(403);
});

test('student cannot apply to vacancy of different role target', function () {
    $vAlumni = JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetAlumni->id,
        'title' => 'Lowongan Khusus Alumni',
        'position' => 'Staff',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->siswaUser, 'sanctum')
        ->postJson("/api/job-vacancies/{$vAlumni->id}/apply", [
            'notes' => 'Siswa melamar lowongan alumni',
        ]);

    $response->assertStatus(403);
});

test('student can apply to matching major or all majors vacancy', function () {
    $vRpl = JobVacancy::create([
        'company_id' => $this->company->id,
        'status_id' => $this->statusPublished->id,
        'target_applicant_id' => $this->targetBoth->id,
        'title' => 'Lowongan RPL Siswa',
        'position' => 'Junior Developer',
        'is_active' => true,
    ]);
    $vRpl->majors()->attach($this->majorRpl->id);

    $response = $this->actingAs($this->siswaUser, 'sanctum')
        ->postJson("/api/job-vacancies/{$vRpl->id}/apply", [
            'notes' => 'Melamar posisi RPL',
        ]);

    $response->assertCreated();
});
