<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'student_alumni_id',
    'career_status',

    // Status: Bekerja
    'company_name',
    'job_title',
    'minimum_salary',
    'maximum_salary',
    'waiting_period',
    'start_date',

    // Status: Wirausaha
    'business_name',
    'business_address',
    'instagram_handle',
    'average_income',
    'business_field',
    'business_start_date',

    // Status: Lanjut Studi
    'university_name',
    'study_program',

    // Audit Trail
    'created_by',
    'updated_by',
    'deleted_by',
])]
class TracerStudy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tracer_studies';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_salary'      => 'integer',
            'maximum_salary'      => 'integer',
            'start_date'          => 'date',
            'business_start_date' => 'date',
        ];
    }

    public function studentAlumni(): BelongsTo
    {
        return $this->belongsTo(StudentAlumni::class, 'student_alumni_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}