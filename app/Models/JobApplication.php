<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'job_vacancy_id',
    'student_alumni_id',
    'status_id',
    'current_stage_id',
    'applied_at',
    'notes',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_applications';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
        ];
    }

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function studentAlumni(): BelongsTo
    {
        return $this->belongsTo(StudentAlumni::class, 'student_alumni_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'status_id')
            ->byCategory('job_application_status');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(SelectionStage::class, 'current_stage_id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(ApplicationStageHistory::class, 'job_application_id')->orderBy('created_at', 'asc');
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
