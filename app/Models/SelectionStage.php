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
    'stage_type_id',
    'name',
    'sequence_order',
    'description',
    'minimum_score',
    'scheduled_at',
    'location',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class SelectionStage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'selection_stages';

    protected function casts(): array
    {
        return [
            'sequence_order' => 'integer',
            'minimum_score' => 'decimal:2',
            'scheduled_at' => 'datetime',
        ];
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(ApplicationStageHistory::class, 'selection_stage_id');
    }

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function stageType(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'stage_type_id')
            ->byCategory('stage_type');
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
