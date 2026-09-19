<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'job_application_id',
    'student_alumni_id',
    'company_id',
    'placement_status_id',
    'accepted_date',
    'start_date',
    'notes',
    'evaluations',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class JobPlacement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_placements';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_date' => 'date',
            'start_date' => 'date',
            'evaluations' => 'array',
        ];
    }

    public static function isTerminalStatus(?string $status): bool
    {
        if (! $status) {
            return false;
        }
        $s = strtolower($status);

        return str_contains($s, 'resign')
            || str_contains($s, 'kontrak')
            || str_contains($s, 'habis')
            || str_contains($s, 'pindah')
            || str_contains($s, 'keluar')
            || str_contains($s, 'non-aktif')
            || in_array($s, ['resigned', 'moved', 'contract_end', 'terminated']);
    }

    public function getEvaluationStatusForPeriod(int $months): string
    {
        $evaluations = is_array($this->evaluations) ? $this->evaluations : [];
        $eval3 = $evaluations['3']['status'] ?? null;
        $eval6 = $evaluations['6']['status'] ?? null;
        $eval12 = $evaluations['12']['status'] ?? null;

        $status3 = $eval3 ?? $this->calculateFallbackEvaluationStatus(3);
        $isTerminatedAt3 = self::isTerminalStatus($status3);

        if ($months === 3) {
            return $status3;
        }

        if ($isTerminatedAt3) {
            return '-';
        }

        $status6 = $eval6 ?? $this->calculateFallbackEvaluationStatus(6);
        $isTerminatedAt6 = self::isTerminalStatus($status6);

        if ($months === 6) {
            return $status6;
        }

        if ($isTerminatedAt6) {
            return '-';
        }

        $status12 = $eval12 ?? $this->calculateFallbackEvaluationStatus(12);

        return $status12;
    }

    public function calculateFallbackEvaluationStatus(int $months): string
    {
        if (! $this->start_date) {
            return 'Belum Waktunya';
        }

        if (now()->lt($this->start_date->copy()->addMonths($months))) {
            return 'Belum Waktunya';
        }

        $statusCode = strtolower($this->placementStatus?->code ?? '');
        $statusName = strtolower($this->placementStatus?->name ?? '');

        if ($statusCode === 'resigned' || str_contains($statusName, 'resign') || str_contains($statusName, 'keluar')) {
            return 'Resign / Kontrak Habis';
        }

        if ($statusCode === 'moved' || $statusCode === 'contract_end' || str_contains($statusName, 'pindah') || str_contains($statusName, 'kontrak') || str_contains($statusName, 'habis')) {
            return 'Pindah Perusahaan Lain';
        }

        return 'Masih Bekerja / Aktif';
    }

    public function isRetainedAtMonths(int $months): bool
    {
        $status = $this->getEvaluationStatusForPeriod($months);
        if ($status === '-' || $status === 'Belum Waktunya' || empty($status)) {
            return false;
        }

        if (self::isTerminalStatus($status)) {
            return false;
        }

        $s = strtolower($status);

        return $s === 'active' || str_contains($s, 'masih bekerja') || str_contains($s, 'aktif') || str_contains($s, 'bertahan');
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function studentAlumni(): BelongsTo
    {
        return $this->belongsTo(StudentAlumni::class, 'student_alumni_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function placementStatus(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'placement_status_id')
            ->byCategory('placement_status');
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
