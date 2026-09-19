<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'student_alumni_id',
    'career_status',

    // Status: Bekerja & Penempatan
    'company_name',
    'company_sector',
    'job_title',
    'job_location',
    'minimum_salary',
    'maximum_salary',
    'waiting_period',
    'accepted_date',
    'start_date',

    // Status: Wirausaha
    'business_name',
    'business_address',
    'instagram_handle',
    'average_income',
    'business_field',
    'business_start_date',

    // Status: Lanjut Studi
    'university_name',
    'study_program',

    // Audit Trail
    'created_by',
    'updated_by',
    'deleted_by',
])]
class TracerStudy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tracer_studies';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_salary' => 'integer',
            'maximum_salary' => 'integer',
            'accepted_date' => 'date',
            'start_date' => 'date',
            'business_start_date' => 'date',
        ];
    }

    public function getStatus12BulanAttribute(): string
    {
        if ($this->career_status === 'lanjut_studi') {
            return 'Masih Kuliah';
        }

        if ($this->career_status === 'wirausaha') {
            return 'Wirausaha';
        }

        if ($this->career_status === 'mencari_pekerjaan') {
            return 'Mencari Kerja';
        }

        // Untuk status bekerja, cek evaluasi job placement jika ada
        $placement = $this->studentAlumni?->jobPlacements?->first();
        if ($placement) {
            $evalStatus = $placement->getEvaluationStatusForPeriod(12);
            if ($evalStatus && $evalStatus !== '-' && $evalStatus !== 'Belum Waktunya') {
                return $evalStatus;
            }
        }

        return 'Masih Bekerja';
    }

    public function studentAlumni(): BelongsTo
    {
        return $this->belongsTo(StudentAlumni::class, 'student_alumni_id');
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
