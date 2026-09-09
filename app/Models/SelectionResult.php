<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'job_application_id',
    'admin_selection_status',
    'psychotest_score',
    'interview_score',
    'mcu_score',
    'final_score',
    'decision',
    'status',
    'notes',
    'created_by',
    'updated_by',
])]
class SelectionResult extends Model
{
    use HasFactory;

    protected $table = 'selection_results';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'psychotest_score' => 'decimal:2',
            'interview_score' => 'decimal:2',
            'mcu_score' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
