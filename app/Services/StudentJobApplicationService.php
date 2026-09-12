<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobApplication;
use App\Models\StudentAlumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 10;

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
            ])
            ->firstOrFail();
    }
}
