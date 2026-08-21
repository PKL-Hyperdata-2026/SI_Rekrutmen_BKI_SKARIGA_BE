<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\JobPlacement;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class JobPlacementService
{
    /** @var array<int, string> */
    protected array $sortableColumns = [
        'id',
        'accepted_date',
        'start_date',
        'created_at',
    ];

    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = JobPlacement::with([
            'studentAlumni.user',
            'studentAlumni.major',
            'company',
            'placementStatus',
            'jobApplication.jobVacancy',
        ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('studentAlumni.user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('studentAlumni', function (Builder $alumniQuery) use ($search) {
                        $alumniQuery->where('nis', 'like', "%{$search}%");
                    })
                    ->orWhereHas('company', function (Builder $companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['student_alumni_id'])) {
            $query->where('student_alumni_id', $filters['student_alumni_id']);
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['placement_status_id'])) {
            $query->where('placement_status_id', $filters['placement_status_id']);
        }

        if (! empty($filters['job_application_id'])) {
            $query->where('job_application_id', $filters['job_application_id']);
        }

        if (isset($filters['year']) && $filters['year'] !== '') {
            $year = (int) $filters['year'];
            $query->where(function (Builder $q) use ($year) {
                $q->whereYear('accepted_date', $year)
                    ->orWhereYear('start_date', $year);
            });
        }

        $sortBy = isset($filters['sort_by']) && in_array($filters['sort_by'], $this->sortableColumns, true)
            ? $filters['sort_by']
            : 'id';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function show(JobPlacement $jobPlacement): JobPlacement
    {
        return $jobPlacement->load([
            'studentAlumni.user',
            'studentAlumni.major',
            'company',
            'placementStatus',
            'jobApplication.jobVacancy',
        ]);
    }

    public function create(array $data, ?int $actorId = null): JobPlacement
    {
        return DB::transaction(function () use ($data, $actorId) {
            $data['created_by'] = $actorId;
            $data['updated_by'] = $actorId;

            $placement = JobPlacement::create($data);

            return $placement->load([
                'studentAlumni.user',
                'studentAlumni.major',
                'company',
                'placementStatus',
                'jobApplication.jobVacancy',
            ]);
        });
    }

    public function update(JobPlacement $jobPlacement, array $data, ?int $actorId = null): JobPlacement
    {
        return DB::transaction(function () use ($jobPlacement, $data, $actorId) {
            $data['updated_by'] = $actorId;

            $jobPlacement->update($data);

            return $jobPlacement->fresh([
                'studentAlumni.user',
                'studentAlumni.major',
                'company',
                'placementStatus',
                'jobApplication.jobVacancy',
            ]);
        });
    }

    public function delete(JobPlacement $jobPlacement, ?int $actorId = null): bool
    {
        return DB::transaction(function () use ($jobPlacement, $actorId) {
            $jobPlacement->updated_by = $actorId;
            $jobPlacement->deleted_by = $actorId;
            $jobPlacement->save();

            return (bool) $jobPlacement->delete();
        });
    }

    public function getFormOptions(): array
    {
        $companies = Company::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $placementStatuses = StandardType::byCategory('placement_status')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'metadata']);

        $studentsAlumni = StudentAlumni::with(['user:id,full_name', 'major:id,name'])
            ->where('is_active', true)
            ->get(['id', 'user_id', 'major_id', 'nis'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'nis' => $item->nis,
                'fullName' => $item->user?->full_name,
                'majorName' => $item->major?->name,
            ]);

        return [
            'companies' => $companies,
            'placement_statuses' => $placementStatuses,
            'students_alumni' => $studentsAlumni,
        ];
    }
}
