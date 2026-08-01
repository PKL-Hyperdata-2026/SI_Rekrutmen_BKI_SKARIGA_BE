<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobVacancy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_vacancies';

    protected $fillable = [
        'company_id',
        'job_type_id',
        'status_id',
        'target_applicant_id',
        'title',
        'slug',
        'position',
        'description',
        'qualification',
        'quota',
        'deadline',
        'work_location',
        'min_salary',
        'max_salary',
        'is_featured',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'quota' => 'integer',
        'deadline' => 'date',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function jobType(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'job_type_id')
            ->byCategory('job_type');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'status_id')
            ->byCategory('vacancy_status');
    }

    public function targetApplicant(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'target_applicant_id')
            ->byCategory('target_applicant');
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'job_vacancy_majors', 'job_vacancy_id', 'major_id')->withTimestamps();
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
