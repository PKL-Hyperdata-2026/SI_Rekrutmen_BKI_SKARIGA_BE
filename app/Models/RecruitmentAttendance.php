<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stage_history_id',
    'attendance_status_id',
    'qr_code_token',
    'attended_at',
    'latitude',
    'longitude',
    'photo_selfie_path',
])]
class RecruitmentAttendance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recruitment_attendances';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
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
}
