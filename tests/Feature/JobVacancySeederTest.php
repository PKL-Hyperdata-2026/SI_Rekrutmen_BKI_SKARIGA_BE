<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\StandardType;
use Database\Seeders\CompanyIndustrySeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\JobVacancySeeder;
use Database\Seeders\JobVacancyStandardTypeSeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        UserSeeder::class,
        CompanyIndustrySeeder::class,
        CompanySeeder::class,
        DepartmentSeeder::class,
        MajorSeeder::class,
        JobVacancyStandardTypeSeeder::class,
        JobVacancySeeder::class,
    ]);
});

test('job vacancy seeder creates exactly 30 realistic vacancies across 10 companies', function (): void {
    expect(JobVacancy::count())->toBe(30);

    $alumniTarget = StandardType::byCategory('target_applicant')->where('code', 'alumni_only')->first();
    $class12Target = StandardType::byCategory('target_applicant')->where('code', 'class_12_only')->first();
    $bothTarget = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->first();

    expect(JobVacancy::where('target_applicant_id', $alumniTarget->id)->count())->toBe(10);
    expect(JobVacancy::where('target_applicant_id', $class12Target->id)->count())->toBe(10);
    expect(JobVacancy::where('target_applicant_id', $bothTarget->id)->count())->toBe(10);

    $topCompanies = [
        'PT Kejayaan Terraloka',
        'PT Hyperdata Solusindo Mandiri',
        'PT Bank Central Asia',
        'PT Perusahaan Listrik Negara',
        'PT Telkom Indonesia',
    ];

    foreach ($topCompanies as $name) {
        $company = Company::where('name', $name)->first();
        expect($company)->not->toBeNull();
        expect(JobVacancy::where('company_id', $company->id)->count())->toBe(3);
    }

    $distinctCompanyCount = JobVacancy::distinct('company_id')->count('company_id');
    expect($distinctCompanyCount)->toBe(10);

    $vacanciesWithoutMajors = JobVacancy::doesntHave('majors')->count();
    expect($vacanciesWithoutMajors)->toBe(0);

    $invalidVacancies = JobVacancy::whereNull('position')
        ->orWhereNull('description')
        ->orWhereNull('qualification')
        ->orWhereNull('work_location')
        ->orWhere('quota', '<=', 0)
        ->count();

    expect($invalidVacancies)->toBe(0);
});

test('job vacancy seeder is idempotent', function (): void {
    $this->seed(JobVacancySeeder::class);

    expect(JobVacancy::count())->toBe(30);
});
