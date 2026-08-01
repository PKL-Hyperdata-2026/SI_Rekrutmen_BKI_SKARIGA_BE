<?php

use App\Models\StandardType;
use Database\Seeders\ClassSeeder;
use Database\Seeders\EmploymentStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('employment_status and class lookups are seeded', function () {
    $this->seed(EmploymentStatusSeeder::class);
    $this->seed(ClassSeeder::class);

    $employmentStatuses = StandardType::byCategory('employment_status')->get();
    $classes = StandardType::byCategory('class')->get();

    expect($employmentStatuses)->not->toBeEmpty();
    expect($employmentStatuses->pluck('code'))->toContain('mencari_kerja', 'bekerja', 'kuliah', 'wirausaha');
    expect($classes)->not->toBeEmpty();
    expect($classes->pluck('code'))->toContain('xii_rpl_1');
});

test('seeders are idempotent', function () {
    $this->seed([EmploymentStatusSeeder::class, ClassSeeder::class]);
    $this->seed([EmploymentStatusSeeder::class, ClassSeeder::class]);

    expect(StandardType::byCategory('employment_status')->count())->toBe(4);
});
