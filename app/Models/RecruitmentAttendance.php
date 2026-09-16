<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stage_history_id',
    'attendance_status_id',
    'validation_status',
    'validated_by',
    'validated_at',
    'attended_at',
    'notes',
    'system_action',
])]
class RecruitmentAttendance extends Model
{
    use HasFactory;

    protected $table = 'recruitment_attendances';

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function stageHistory(): BelongsTo
    {
        return $this->belongsTo(ApplicationStageHistory::class, 'stage_history_id');
    }

    public function attendanceStatus(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'attendance_status_id')
            ->byCategory('attendance_status');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('validation_status', 'pending');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('validation_status', 'verified');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('validation_status', 'rejected');
    }
}
