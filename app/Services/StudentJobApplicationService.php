<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobApplication;
use App\Models\StudentAlumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StudentJobApplicationService
{
    /**
     * Get paginated list of applications submitted by the given user.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getMyApplications(int $userId, array $filters = []): LengthAwarePaginator
    {
        $student = StudentAlumni::where('user_id', $userId)->firstOrFail();

        $query = JobApplication::where('student_alumni_id', $student->id)
            ->with([
                'jobVacancy.company',
                'jobVacancy.jobType',
                'status',
                'currentStage',
                'stageHistories.selectionStage',
                'stageHistories.status',
                'jobPlacement',
                'selectionResult',
            ]);

        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (! empty($filters['status_code']) && $filters['status_code'] !== 'all') {
            $statusCode = $filters['status_code'];
            $query->whereHas('status', function (Builder $q) use ($statusCode): void {
                if ($statusCode === 'in_progress') {
                    $q->whereIn('code', ['in_progress', 'pending']);
                } else {
                    $q->where('code', $statusCode);
                }
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('jobVacancy', function (Builder $q) use ($search): void {
                $q->where(function (Builder $sub) use ($search): void {
                    $sub->where('title', 'ilike', "%{$search}%")
                        ->orWhere('position', 'ilike', "%{$search}%")
                        ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'ilike', "%{$search}%"));
                });
            });
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('applied_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('applied_at', '<=', $filters['end_date']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 5;

        return $query->orderBy('applied_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get detail of a specific application for the given user.
     */
    public function getMyApplicationDetail(int $userId, int $applicationId): JobApplication
    {
        $student = StudentAlumni::where('user_id', $userId)->firstOrFail();

        return JobApplication::where('id', $applicationId)
            ->where('student_alumni_id', $student->id)
            ->with([
                'jobVacancy.company',
                'jobVacancy.jobType',
                'status',
                'currentStage',
                'stageHistories.selectionStage',
                'stageHistories.status',
                'stageHistories.assessor',
                'jobPlacement',
                'selectionResult',
            ])
            ->firstOrFail();
    }
}
