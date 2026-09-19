<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobVacancyService
{
    public function __construct(protected NotificationService $notificationService) {}

    public function generateUniqueSlug(string $title, int $companyId): string
    {
        $baseSlug = Str::slug($title);
        $slug = "{$baseSlug}-{$companyId}-".Str::random(5);

        while (JobVacancy::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$companyId}-".Str::random(6);
        }

        return $slug;
    }

    public function getAdminVacancies(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy', 'updatedBy']);
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('work_location', 'like', "%{$search}%")
                    ->orWhereHas('company', function (Builder $companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('majors', function (Builder $majorQuery) use ($search) {
                        $majorQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (! empty($filters['target_applicant_id']) && $filters['target_applicant_id'] !== 'all') {
            $targetApplicantId = $filters['target_applicant_id'];
            $query->where(function (Builder $q) use ($targetApplicantId) {
                $q->where('target_applicant_id', $targetApplicantId)
                    ->orWhereNull('target_applicant_id');
            });
        }

        if (! empty($filters['job_type_id'])) {
            $query->where('job_type_id', $filters['job_type_id']);
        }

        if (! empty($filters['major_id']) && $filters['major_id'] !== 'all') {
            $majorId = $filters['major_id'];
            $query->where(function (Builder $q) use ($majorId) {
                $q->whereHas('majors', function (Builder $majorQuery) use ($majorId) {
                    $majorQuery->where('majors.id', $majorId);
                })->orDoesntHave('majors');
            });
        }

        if (! empty($filters['major_ids']) && is_array($filters['major_ids'])) {
            $majorIds = $filters['major_ids'];
            $query->whereHas('majors', function (Builder $majorQuery) use ($majorIds) {
                $majorQuery->whereIn('majors.id', $majorIds);
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->latest()->paginate($perPage);
    }

    public function createJobVacancy(array $data, ?int $userId = null): JobVacancy
    {
        return DB::transaction(function () use ($data, $userId) {
            $title = ! empty($data['title']) ? $data['title'] : ($data['position'] ?? 'Lowongan Kerja');
            $data['title'] = $title;
            $data['slug'] = $this->generateUniqueSlug($title, $data['company_id']);
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            // Default status to 'published' if not provided
            if (empty($data['status_id'])) {
                $publishedStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->first();
                if ($publishedStatus) {
                    $data['status_id'] = $publishedStatus->id;
                }
            }

            // Default job_type to first available job_type if not provided
            if (empty($data['job_type_id'])) {
                $jobType = StandardType::byCategory('job_type')->first();
                if ($jobType) {
                    $data['job_type_id'] = $jobType->id;
                }
            }

            // Fallback description if not provided
            if (empty($data['description'])) {
                $data['description'] = $data['qualification'] ?? "Lowongan pekerjaan untuk posisi {$title}.";
            }

            $majorIds = $data['major_ids'] ?? [];
            unset($data['major_ids']);

            $sendNotification = ! empty($data['send_notification']);
            unset($data['send_notification']);

            $vacancy = JobVacancy::create($data);

            if (! empty($majorIds)) {
                $vacancy->majors()->sync($majorIds);
            }

            $vacancy->load(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy', 'updatedBy']);

            if ($sendNotification) {
                DB::afterCommit(function () use ($vacancy) {
                    $this->notifyTargetApplicants($vacancy);
                });
            }

            return $vacancy;
        });
    }

    public function updateJobVacancy(JobVacancy $vacancy, array $data, ?int $userId = null): JobVacancy
    {
        return DB::transaction(function () use ($vacancy, $data, $userId) {
            if (isset($data['position']) && empty($data['title'])) {
                $data['title'] = $data['position'];
            }

            if (isset($data['title']) && $data['title'] !== $vacancy->title) {
                $companyId = $data['company_id'] ?? $vacancy->company_id;
                $data['slug'] = $this->generateUniqueSlug($data['title'], $companyId);
            }

            $data['updated_by'] = $userId;

            if (array_key_exists('major_ids', $data)) {
                $vacancy->majors()->sync($data['major_ids'] ?? []);
                unset($data['major_ids']);
            }

            $sendNotification = ! empty($data['send_notification']);
            unset($data['send_notification']);

            $vacancy->update($data);
            $vacancy = $vacancy->fresh(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy', 'updatedBy']);

            if ($sendNotification) {
                DB::afterCommit(function () use ($vacancy) {
                    $this->notifyTargetApplicants($vacancy);
                });
            }

            return $vacancy;
        });
    }

    public function deleteJobVacancy(JobVacancy $vacancy, ?int $userId = null): bool
    {
        $vacancy->updated_by = $userId;
        $vacancy->deleted_by = $userId;
        $vacancy->save();

        return $vacancy->delete();
    }

    public function toggleActive(JobVacancy $vacancy, ?int $userId = null): JobVacancy
    {
        $vacancy->is_active = ! $vacancy->is_active;
        $vacancy->updated_by = $userId;
        $vacancy->save();

        return $vacancy->fresh(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy', 'updatedBy']);
    }

    public function getJobVacancyDetail(string $idOrSlug): JobVacancy
    {
        return JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy', 'updatedBy'])
            ->where(function ($query) use ($idOrSlug) {
                $query->where('id', $idOrSlug)
                    ->orWhere('slug', $idOrSlug);
            })
            ->firstOrFail();
    }

    public function findJobVacancyDetail(string $idOrSlug): ?JobVacancy
    {
        return JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy', 'updatedBy'])
            ->where(function ($query) use ($idOrSlug) {
                $query->where('id', $idOrSlug)
                    ->orWhere('slug', $idOrSlug);
            })
            ->first();
    }

    public function getHrdVacancyDetail(int $companyId, string $idOrSlug): ?JobVacancy
    {
        $vacancy = $this->findJobVacancyDetail($idOrSlug);
        if (! $vacancy || $vacancy->company_id !== $companyId) {
            return null;
        }

        return $vacancy;
    }

    public function getFormOptions(): array
    {
        $companies = Company::where('is_active', true)
            ->select('id', 'name', 'email', 'phone', 'logo_path')
            ->orderBy('name')
            ->get();

        $majors = Major::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $vacancyStatuses = StandardType::byCategory('vacancy_status')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'metadata']);

        $targetApplicants = StandardType::byCategory('target_applicant')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'metadata']);

        $jobTypes = StandardType::byCategory('job_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'metadata']);

        return [
            'companies' => $companies,
            'majors' => $majors,
            'vacancyStatuses' => $vacancyStatuses,
            'targetApplicants' => $targetApplicants,
            'jobTypes' => $jobTypes,
        ];
    }

    public function getHrdVacancies(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $rejectedStatusId = StandardType::byCategory('job_application_status')->where('code', 'rejected')->first()?->id;
        $today = now()->toDateString();

        $query = JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy', 'updatedBy'])
            ->where('company_id', $companyId)
            ->withCount(['applications as applicants_count' => function ($q) use ($rejectedStatusId) {
                if ($rejectedStatusId) {
                    $q->where('status_id', '!=', $rejectedStatusId);
                }
            }]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('position', 'like', "%{$search}%")
                    ->orWhere('work_location', 'like', "%{$search}%")
                    ->orWhereHas('majors', function (Builder $majorQuery) use ($search) {
                        $majorQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (! empty($filters['target_applicant_id']) && $filters['target_applicant_id'] !== 'all') {
            $query->where('target_applicant_id', $filters['target_applicant_id']);
        }

        if (! empty($filters['job_type_id'])) {
            $query->where('job_type_id', $filters['job_type_id']);
        }

        if (! empty($filters['major_id']) && $filters['major_id'] !== 'all') {
            $query->whereHas('majors', fn ($q) => $q->where('majors.id', $filters['major_id']));
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $this->applyEffectiveStatusFilter($query, $filters['effective_status'] ?? null, $today, $rejectedStatusId);
        $this->applyVacancySort($query, $filters['sort'] ?? null, $today, $rejectedStatusId);

        return $query->paginate($perPage);
    }

    /**
     * Count subquery for active (non-rejected, non-trashed) applications.
     * Mirrors the applicants_count withCount above so filters and sorts
     * use the exact same definition the API resource exposes.
     *
     * @param  array<int|string>  $bindings
     */
    private function activeApplicantsCountSql(?int $rejectedStatusId, array &$bindings): string
    {
        $table = (new JobApplication)->getTable();
        $sql = "(SELECT COUNT(*) FROM {$table} WHERE {$table}.job_vacancy_id = job_vacancies.id AND {$table}.deleted_at IS NULL";
        if ($rejectedStatusId) {
            $sql .= " AND {$table}.status_id != ?";
            $bindings[] = $rejectedStatusId;
        }

        return $sql.')';
    }

    /**
     * Effective status combines the raw is_active flag with deadline and
     * quota fullness. Unknown values are ignored on purpose so old
     * clients keep working.
     */
    private function applyEffectiveStatusFilter(Builder $query, mixed $status, string $today, ?int $rejectedStatusId): void
    {
        if (! is_string($status) || $status === '' || $status === 'all') {
            return;
        }

        $bindings = [];
        $countSql = $this->activeApplicantsCountSql($rejectedStatusId, $bindings);

        match ($status) {
            'active' => $query->where('job_vacancies.is_active', true)
                ->where(function (Builder $q) use ($today) {
                    $q->whereNull('job_vacancies.deadline')
                        ->orWhere('job_vacancies.deadline', '>=', $today);
                })
                ->where(function (Builder $q) use ($countSql, $bindings) {
                    $q->where('job_vacancies.quota', '<=', 0)
                        ->orWhereRaw("{$countSql} < job_vacancies.quota", $bindings);
                }),
            'closed' => $query->where(function (Builder $q) use ($today, $countSql, $bindings) {
                $q->where('job_vacancies.is_active', false)
                    ->orWhere(function (Builder $expired) use ($today) {
                        $expired->whereNotNull('job_vacancies.deadline')
                            ->where('job_vacancies.deadline', '<', $today);
                    })
                    ->orWhere(function (Builder $full) use ($countSql, $bindings) {
                        $full->where('job_vacancies.quota', '>', 0)
                            ->whereRaw("{$countSql} >= job_vacancies.quota", $bindings);
                    });
            }),
            'quota_full' => $query->where('job_vacancies.quota', '>', 0)
                ->whereRaw("{$countSql} >= job_vacancies.quota", $bindings),
            'expiring' => $query->where('job_vacancies.is_active', true)
                ->whereNotNull('job_vacancies.deadline')
                ->whereBetween('job_vacancies.deadline', [$today, now()->addDays(7)->toDateString()])
                ->where(function (Builder $q) use ($countSql, $bindings) {
                    $q->where('job_vacancies.quota', '<=', 0)
                        ->orWhereRaw("{$countSql} < job_vacancies.quota", $bindings);
                }),
            default => null,
        };
    }

    /**
     * Unknown sort values fall back to newest first so old clients
     * keep working.
     */
    private function applyVacancySort(Builder $query, mixed $sort, string $today, ?int $rejectedStatusId): void
    {
        if ($sort === 'deadline') {
            // Upcoming deadlines first, expired ones at the bottom.
            $query->orderByRaw('CASE WHEN job_vacancies.deadline IS NULL OR job_vacancies.deadline >= ? THEN 0 ELSE 1 END', [$today])
                ->orderBy('job_vacancies.deadline', 'asc')
                ->orderBy('job_vacancies.id', 'desc');

            return;
        }

        if ($sort === 'quota') {
            // Fullest quota ratio first. CASE avoids NULLS/division
            // dialect differences between PostgreSQL and SQLite.
            $bindings = [];
            $countSql = $this->activeApplicantsCountSql($rejectedStatusId, $bindings);
            $query->orderByRaw("CASE WHEN job_vacancies.quota > 0 THEN ({$countSql}) * 1.0 / job_vacancies.quota ELSE -1 END DESC", $bindings)
                ->orderBy('job_vacancies.id', 'desc');

            return;
        }

        $query->latest();
    }

    public function getHrdFormOptions(Company|int $company): array
    {
        $companyModel = $company instanceof Company ? $company : Company::find($company);
        $companyId = $companyModel?->id ?? (int) $company;

        $majors = Major::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $targetApplicants = StandardType::byCategory('target_applicant')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'metadata']);

        $jobTypes = StandardType::byCategory('job_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'metadata']);

        $vacancyStatuses = StandardType::byCategory('vacancy_status')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'metadata']);

        $statistics = $this->getHrdStatistics($companyId);

        return [
            'company' => $companyModel ? [
                'id' => encrypt($companyModel->id),
                'name' => $companyModel->name,
                'email' => $companyModel->email,
                'phone' => $companyModel->phone,
            ] : null,
            'statistics' => $statistics,
            'majors' => $majors,
            'targetApplicants' => $targetApplicants,
            'jobTypes' => $jobTypes,
            'vacancyStatuses' => $vacancyStatuses,
        ];
    }

    public function getHrdStatistics(int $companyId): array
    {
        $activeStatus = StandardType::byCategory('vacancy_status')->where('code', 'published')->first()?->id;

        $totalCount = JobVacancy::where('company_id', $companyId)->count();

        $activeCount = JobVacancy::where('company_id', $companyId)
            ->withCount(['applications as applicants_count' => function ($q) {
                $rejectedStatusId = StandardType::byCategory('job_application_status')->where('code', 'rejected')->first()?->id;
                if ($rejectedStatusId) {
                    $q->where('status_id', '!=', $rejectedStatusId);
                }
            }])
            ->where('is_active', true)
            ->where('status_id', $activeStatus)
            ->where(function (Builder $q): void {
                $q->whereNull('deadline')->orWhere('deadline', '>=', now()->toDateString());
            })
            ->get()
            ->filter(fn (JobVacancy $v) => empty($v->quota) || $v->applicants_count < $v->quota)
            ->count();

        $draftClosedCount = $totalCount - $activeCount;

        return [
            'activeCount' => $activeCount,
            'draftOrClosedCount' => $draftClosedCount,
            'totalCount' => $totalCount,
        ];
    }

    protected function notifyTargetApplicants(JobVacancy $vacancy): void
    {
        $targetCode = $vacancy->targetApplicant?->code;
        $query = User::where('is_active', true);

        if ($targetCode === 'class_12_only') {
            $query->where('role', 'siswa');
        } elseif ($targetCode === 'alumni_only') {
            $query->where('role', 'alumni');
        } else {
            $query->whereIn('role', ['siswa', 'alumni']);
        }

        $userIds = $query->pluck('id')->toArray();

        if (! empty($userIds)) {
            $companyName = $vacancy->company?->name ?? 'Perusahaan Mitra';
            $title = "Lowongan Baru: {$vacancy->position}";
            $message = "{$companyName} membuka lowongan baru untuk posisi {$vacancy->position}. Kuota: {$vacancy->quota} orang.";

            $this->notificationService->sendMultiple(
                $userIds,
                'job_vacancy',
                $title,
                $message,
                [
                    'job_vacancy_id' => $vacancy->id,
                    'slug' => $vacancy->slug,
                    'company_name' => $companyName,
                    'position' => $vacancy->position,
                    'quota' => $vacancy->quota,
                ]
            );
        }
    }
}
