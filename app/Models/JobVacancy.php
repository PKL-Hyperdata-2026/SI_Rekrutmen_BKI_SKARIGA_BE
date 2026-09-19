<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
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
])]
class JobVacancy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_vacancies';

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'deadline' => 'date',
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('deadline')
                    ->orWhere('deadline', '>=', now()->toDateString());
            });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('deadline')
            ->where('deadline', '<', now()->toDateString());
    }

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

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_vacancy_id');
    }

    public function selectionStages(): HasMany
    {
        return $this->hasMany(SelectionStage::class, 'job_vacancy_id')->orderBy('sequence_order', 'asc');
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_vacancy_id');
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
