<?php

declare(strict_types=1);

use App\Models\Major;
use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use App\Models\StudentAlumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('model persists and resolves relations', function () {
    $user = User::factory()->create(['role' => 'siswa']);
    $major = Major::firstOrCreate(
        ['code' => 'RPL'],
        ['name' => 'Rekayasa Perangkat Lunak', 'description' => null, 'is_active' => true]
    );
    $category = StandardTypeCategory::firstOrCreate(
        ['code' => 'employment_status'],
        ['name' => 'Status Kerja', 'description' => null]
    );
    $status = StandardType::create([
        'category_id' => $category->id,
        'code' => 'mencari_kerja',
        'name' => 'Mencari Kerja',
        'is_active' => true,
    ]);

    $student = StudentAlumni::create([
        'user_id' => $user->id,
        'major_id' => $major->id,
        'employment_status_id' => $status->id,
        'nis' => '1234567890',
        'social_media' => ['linkedin' => 'https://linkedin.com/in/jon'],
        'is_active' => true,
    ]);

    expect($student->user->id)->toBe($user->id);
    expect($student->major->id)->toBe($major->id);
    expect($student->employmentStatus->id)->toBe($status->id);
    expect($student->social_media)->toBe(['linkedin' => 'https://linkedin.com/in/jon']);
});
