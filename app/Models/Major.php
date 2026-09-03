<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'department_id',
    'code',
    'name',
    'description',
    'is_active',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class Major extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function studentsAlumni(): HasMany
    {
        return $this->hasMany(StudentAlumni::class, 'major_id');
    }

    public function jobVacancies(): BelongsToMany
    {
        return $this->belongsToMany(JobVacancy::class, 'job_vacancy_majors', 'major_id', 'job_vacancy_id')->withTimestamps();
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
