<?php

declare(strict_types=1);

use App\Models\StandardType;
use Database\Seeders\ClassSeeder;
use Database\Seeders\TracerStudyStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('employment_status and class lookups are seeded', function () {
    $this->seed(TracerStudyStandardTypeSeeder::class);
    $this->seed(ClassSeeder::class);

    $employmentStatuses = StandardType::byCategory('employment_status')->get();
    $classes = StandardType::byCategory('class')->get();

    expect($employmentStatuses)->not->toBeEmpty();
    expect($employmentStatuses->pluck('code'))->toContain('working', 'studying', 'entrepreneur', 'job_seeking');
    expect($classes)->not->toBeEmpty();
    expect($classes->pluck('code'))->toContain('xii_rpl_1');
});

test('seeders are idempotent', function () {
    $this->seed([TracerStudyStandardTypeSeeder::class, ClassSeeder::class]);
    $this->seed([TracerStudyStandardTypeSeeder::class, ClassSeeder::class]);

    expect(StandardType::byCategory('employment_status')->count())->toBe(4);
});
