<?php

use App\Models\Company;
use App\Models\Major;
use App\Models\StudentAlumni;
use Database\Seeders\ClassSeeder;
use Database\Seeders\CompanyIndustrySeeder;
use Database\Seeders\EmploymentStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('factory creates a valid student alumni record', function () {
    $this->seed([
        CompanyIndustrySeeder::class,
        EmploymentStatusSeeder::class,
        ClassSeeder::class,
    ]);

    $major = Major::firstOrCreate(
        ['code' => 'RPL'],
        ['name' => 'Rekayasa Perangkat Lunak', 'description' => null, 'is_active' => true]
    );

    $student = StudentAlumni::factory()->create([
        'major_id' => $major->id,
        'current_company_id' => Company::factory()->create()->id,
    ]);

    expect($student->nis)->not->toBeNull();
    expect($student->user_id)->not->toBeNull();
    expect($student->major_id)->toBe($major->id);
    expect($student->employment_status_id)->not->toBeNull();
    expect($student->class_id)->not->toBeNull();
    expect($student->is_active)->toBeTrue();
});

test('factory works without pre-seeded lookup data via fallbacks', function () {
    $major = Major::firstOrCreate(
        ['code' => 'RPL'],
        ['name' => 'Rekayasa Perangkat Lunak', 'description' => null, 'is_active' => true]
    );

    $student = StudentAlumni::factory()->create(['major_id' => $major->id]);

    expect($student->employment_status_id)->not->toBeNull();
    expect($student->class_id)->not->toBeNull();
});
