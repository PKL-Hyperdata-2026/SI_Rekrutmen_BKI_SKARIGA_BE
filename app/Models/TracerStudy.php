<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TracerStudy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tracer_studies';

    protected $fillable = [
        'student_alumni_id',
        'employment_status_id',
        'survey_year',
        'company_name',
        'job_title',
        'relevance_status_id',
        'income_range_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'survey_year' => 'integer',
    ];

    public function studentAlumni(): BelongsTo
    {
        return $this->belongsTo(StudentAlumni::class, 'student_alumni_id');
    }

    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'employment_status_id')
            ->byCategory('employment_status');
    }

    public function relevanceStatus(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'relevance_status_id')
            ->byCategory('relevance_status');
    }

    public function incomeRange(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'income_range_id')
            ->byCategory('income_range');
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