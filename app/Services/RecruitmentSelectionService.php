<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RecruitmentSelectionService
{
    /**
     * Get paginated job applications for admin recruitment selection view.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);

        return $query
            ->with([
                'studentAlumni.user',
                'studentAlumni.major',
                'currentStage',
                'status',
                'selectionResult',
                'stageHistories' => function ($q): void {
                    $q->orderBy('created_at', 'asc')
                        ->with([
                            'selectionStage',
                            'status',
                            'assessor',
                            'attendance.attendanceStatus',
                        ]);
                },
                'jobVacancy.company',
            ])
            ->orderBy('applied_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get summary metrics (filtered same as paginate, but aggregated).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function getSummary(array $filters = []): array
    {
        $base = $this->buildBaseQuery($filters);

        $totalApplicants = (clone $base)->count();

        $totalPassedAdmin = (clone $base)
            ->whereHas('selectionResult', function (Builder $q): void {
                $q->where('admin_selection_status', 'lolos');
            })
            ->count();

        $totalAccepted = (clone $base)
            ->where(function (Builder $q): void {
                $q->whereHas('selectionResult', function (Builder $sr): void {
                    $sr->where('decision', 'diterima');
                })->orWhereHas('status', function (Builder $s): void {
                    $s->where('code', 'accepted');
                });
            })
            ->count();

        return [
            'total_applicants' => $totalApplicants,
            'total_passed_admin' => $totalPassedAdmin,
            'total_accepted' => $totalAccepted,
        ];
    }

    /**
     * Build base query with all filters applied (without pagination / eager loads).
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $query = JobApplication::query();

        if (! empty($filters['job_vacancy_id'])) {
            $query->where('job_vacancy_id', (int) $filters['job_vacancy_id']);
        }

        if (! empty($filters['stage_id'])) {
            $query->where('current_stage_id', (int) $filters['stage_id']);
        }

        if (! empty($filters['attendance_status'])) {
            $status = strtolower((string) $filters['attendance_status']);
            // Normalize: hadir_tidak_hadir / tidak hadir -> tidak_hadir
            $status = str_replace([' ', '-'], '_', $status);

            if ($status === 'hadir' || $status === 'present') {
                $query->whereHas('stageHistories.attendance.attendanceStatus', function (Builder $q): void {
                    $q->where('code', 'present');
                });
            } elseif ($status === 'tidak_hadir' || $status === 'tidakhadir' || $status === 'absent' || $status === 'hadir_tidak_hadir') {
                $query->whereHas('stageHistories.attendance.attendanceStatus', function (Builder $q): void {
                    $q->whereIn('code', ['absent', 'leave']);
                });
            } elseif ($status === 'belum') {
                $query->where(function (Builder $q): void {
                    $q->whereDoesntHave('stageHistories')
                        ->orWhereHas('stageHistories', function (Builder $sh): void {
                            $sh->whereDoesntHave('attendance')
                                ->orWhereHas('attendance', function (Builder $a): void {
                                    $a->whereNull('attendance_status_id');
                                });
                        });
                });
            }
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->whereHas('studentAlumni', function (Builder $sa) use ($search): void {
                $sa->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $u) use ($search): void {
                        $u->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
}
