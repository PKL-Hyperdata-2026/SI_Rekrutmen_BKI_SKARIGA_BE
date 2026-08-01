<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'major_id',
    'employment_status_id',
    'class_id',
    'nis',
    'graduation_year',
    'social_media',
    'current_company_id',
    'current_position',
    'starting_salary',
    'waiting_time_months',
    'is_active',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class StudentAlumni extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'students_alumni';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'social_media' => 'array',
            'starting_salary' => 'decimal:2',
            'waiting_time_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class, 'major_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'class_id')
            ->byCategory('class');
    }

    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'employment_status_id')
            ->byCategory('employment_status');
    }

    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
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
