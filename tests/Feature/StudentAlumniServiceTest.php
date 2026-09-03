<?php

declare(strict_types=1);

use App\Models\Major;
use App\Models\StandardTypeCategory;
use App\Models\StudentAlumni;
use App\Models\User;
use App\Services\StudentAlumniService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makeStudentAlumniPayload(?array $overrides = []): array
{
    $user = User::factory()->create(['role' => 'siswa']);
    $major = Major::firstOrCreate(
        ['code' => 'RPL'],
        ['name' => 'Rekayasa Perangkat Lunak', 'description' => null, 'is_active' => true]
    );
    $category = StandardTypeCategory::firstOrCreate(
        ['code' => 'employment_status'],
        ['name' => 'Status Kerja', 'description' => null]
    );

    return array_merge([
        'user_id' => $user->id,
        'major_id' => $major->id,
        'employment_status_id' => $category->standardTypes()->first()?->id,
        'nis' => fake()->unique()->numerify('#######'),
        'is_active' => true,
    ], $overrides);
}

test('creates a new student alumni record', function () {
    $service = app(StudentAlumniService::class);
    $payload = makeStudentAlumniPayload();

    $student = $service->saveStudentAlumni($payload);

    expect($student)->toBeInstanceOf(StudentAlumni::class);
    expect($student->exists)->toBeTrue();
    expect($student->nis)->toBe($payload['nis']);
    expect(StudentAlumni::count())->toBe(1);
});

test('updates an existing record when id is provided', function () {
    $service = app(StudentAlumniService::class);
    $student = $service->saveStudentAlumni(makeStudentAlumniPayload());

    $updated = $service->saveStudentAlumni([
        ...$student->only(['user_id', 'major_id', 'employment_status_id', 'is_active']),
        'nis' => 'UPDATED123',
    ], $student->id);

    expect($updated->id)->toBe($student->id);
    expect($updated->nis)->toBe('UPDATED123');
    expect(StudentAlumni::count())->toBe(1);
});

test('restores a soft-deleted record with the same nis instead of inserting', function () {
    $service = app(StudentAlumniService::class);
    $payload = makeStudentAlumniPayload();
    $student = $service->saveStudentAlumni($payload);
    $student->delete();

    $restored = $service->saveStudentAlumni($payload);

    expect(StudentAlumni::withTrashed()->count())->toBe(1);
    expect(StudentAlumni::count())->toBe(1);
    expect($restored->id)->toBe($student->id);
    expect($restored->trashed())->toBeFalse();
});

test('rejects when an active record already uses the same nis', function () {
    $service = app(StudentAlumniService::class);
    $payload = makeStudentAlumniPayload();
    $service->saveStudentAlumni($payload);

    $service->saveStudentAlumni($payload);
})->throws(ValidationException::class);
