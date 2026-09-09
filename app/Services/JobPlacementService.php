<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\JobPlacement;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
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

    public function getCompanyByUserId(?int $userId): ?Company
    {
        if (! $userId) {
            return null;
        }

        return Company::where('user_id', $userId)->first();
    }

    public function getCompanyIdByUserId(?int $userId): ?int
    {
        if (! $userId) {
            return null;
        }

        return Company::where('user_id', $userId)->value('id');
    }

    public function belongsToCompany(JobPlacement|int $jobPlacement, int $companyId): bool
    {
        if ($jobPlacement instanceof JobPlacement) {
            return $jobPlacement->company_id === $companyId;
        }

        return JobPlacement::where('id', $jobPlacement)->where('company_id', $companyId)->exists();
    }

    public function show(JobPlacement|int $jobPlacement): JobPlacement
    {
        if (! $jobPlacement instanceof JobPlacement) {
            $jobPlacement = JobPlacement::findOrFail($jobPlacement);
        }

        return $jobPlacement->loadMissing([
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

            if (empty($data['company_id']) && $actorId) {
                $data['company_id'] = $this->getCompanyIdByUserId($actorId);
            }

            if (! empty($data['position']) && ! empty($data['student_alumni_id'])) {
                StudentAlumni::where('id', $data['student_alumni_id'])->update([
                    'current_position' => $data['position'],
                    'current_company_id' => $data['company_id'] ?? null,
                ]);
                if (empty($data['notes'])) {
                    $data['notes'] = $data['position'];
                }
            }

            unset($data['position']);

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

    public function update(JobPlacement|int $jobPlacement, array $data, ?int $actorId = null): JobPlacement
    {
        if (! $jobPlacement instanceof JobPlacement) {
            $jobPlacement = JobPlacement::findOrFail($jobPlacement);
        }

        return DB::transaction(function () use ($jobPlacement, $data, $actorId) {
            $data['updated_by'] = $actorId;

            if (! empty($data['period']) && ! empty($data['work_status'])) {
                $evaluations = $jobPlacement->evaluations ?? [];
                $period = (string) $data['period'];

                if ($period === '6' && empty($evaluations['3']['status'])) {
                    throw new \InvalidArgumentException('Evaluasi monitoring 3 bulan harus diisi terlebih dahulu sebelum 6 bulan.');
                }
                if ($period === '12' && (empty($evaluations['3']['status']) || empty($evaluations['6']['status']))) {
                    throw new \InvalidArgumentException('Evaluasi monitoring 3 bulan dan 6 bulan harus diisi terlebih dahulu sebelum 12 bulan.');
                }

                $evaluations[$period] = [
                    'status' => $data['work_status'],
                    'notes' => $data['notes'] ?? ($evaluations[$period]['notes'] ?? null),
                    'updated_at' => now()->toIso8601String(),
                ];

                $s = strtolower((string) $data['work_status']);
                $isResigned = str_contains($s, 'resign')
                    || str_contains($s, 'kontrak')
                    || str_contains($s, 'habis')
                    || str_contains($s, 'pindah')
                    || str_contains($s, 'keluar')
                    || str_contains($s, 'non-aktif')
                    || in_array($s, ['resigned', 'moved', 'contract_end', 'terminated']);

                if ($isResigned) {
                    if ($period === '3') {
                        unset($evaluations['6'], $evaluations['12']);
                    } elseif ($period === '6') {
                        unset($evaluations['12']);
                    }
                }

                $data['evaluations'] = $evaluations;

                if (empty($data['placement_status_id'])) {
                    $statusCode = match (strtolower((string) $data['work_status'])) {
                        'active', 'masih bekerja / aktif', 'masih bekerja' => 'active',
                        'resigned', 'resign / kontrak habis', 'resign' => 'resigned',
                        'moved', 'pindah perusahaan lain', 'contract_end', 'kontrak selesai' => 'moved',
                        default => null,
                    };

                    if ($statusCode) {
                        $st = StandardType::byCategory('placement_status')->where('code', $statusCode)->first()
                            ?? StandardType::byCategory('placement_status')->where('code', 'contract_end')->first();
                        if ($st) {
                            $data['placement_status_id'] = $st->id;
                        }
                    }
                }
            }

            unset($data['period'], $data['work_status']);

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

    public function delete(JobPlacement|int $jobPlacement, ?int $actorId = null): bool
    {
        if (! $jobPlacement instanceof JobPlacement) {
            $jobPlacement = JobPlacement::findOrFail($jobPlacement);
        }

        return DB::transaction(function () use ($jobPlacement, $actorId) {
            $jobPlacement->updated_by = $actorId;
            $jobPlacement->deleted_by = $actorId;
            $jobPlacement->save();

            return (bool) $jobPlacement->delete();
        });
    }

    public function getFormOptions(?int $companyId = null): array
    {
        $companiesQuery = Company::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name');

        if ($companyId) {
            $companiesQuery->where('id', $companyId);
        }

        $companies = $companiesQuery->get();

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

    public function getMetrics(array $filters = []): array
    {
        $baseQuery = JobPlacement::query();

        if (! empty($filters['company_id'])) {
            $baseQuery->where('company_id', $filters['company_id']);
        }

        if (isset($filters['year']) && $filters['year'] !== '') {
            $year = (int) $filters['year'];
            $baseQuery->where(function (Builder $q) use ($year) {
                $q->whereYear('accepted_date', $year)
                    ->orWhereYear('start_date', $year);
            });
        }

        $placements = (clone $baseQuery)
            ->select(['id', 'start_date', 'evaluations', 'placement_status_id'])
            ->with(['placementStatus:id,code,name'])
            ->get();

        $totalPlacements = $placements->count();

        $count3Months = 0;
        $count6Months = 0;
        $count12Months = 0;

        foreach ($placements as $placement) {
            if ($placement->isRetainedAtMonths(3)) {
                $count3Months++;
            }
            if ($placement->isRetainedAtMonths(6)) {
                $count6Months++;
            }
            if ($placement->isRetainedAtMonths(12)) {
                $count12Months++;
            }
        }

        return [
            'total' => [
                'count' => $totalPlacements,
                'label' => 'Alumni',
                'title' => 'Semua Data',
                'category' => 'TOTAL DITERIMA KERJA',
            ],
            'evaluation3Months' => [
                'count' => $count3Months,
                'label' => 'Bertahan',
                'title' => '3 Bulan',
                'category' => 'EVALUASI',
            ],
            'evaluation6Months' => [
                'count' => $count6Months,
                'label' => 'Bertahan',
                'title' => '6 Bulan',
                'category' => 'EVALUASI',
            ],
            'evaluation12Months' => [
                'count' => $count12Months,
                'label' => 'Bertahan',
                'title' => '12 Bulan',
                'category' => 'EVALUASI',
            ],
        ];
    }
}
