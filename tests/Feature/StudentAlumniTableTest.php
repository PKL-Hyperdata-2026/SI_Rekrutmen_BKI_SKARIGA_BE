<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('students_alumni table exists with expected columns', function () {
    expect(Schema::hasTable('students_alumni'))->toBeTrue();

    $columns = Schema::getColumnListing('students_alumni');

    expect($columns)->toContain(
        'user_id',
        'major_id',
        'employment_status_id',
        'class_id',
        'nis',
        'graduation_year',
        'social_media',
        'current_company_id',
        'current_position',
        'starting_salary',
        'waiting_time_months',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    );
});
