<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobVacancy;
use App\Models\Company;
use Illuminate\Support\Str;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class JobVacancyService
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function generateUniqueSlug(string $title, int $companyId): string
    {
        $baseSlug = Str::slug($title);
        $slug = "{$baseSlug}-{$companyId}-" . Str::random(5);

        while (JobVacancy::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$companyId}-" . Str::random(6);
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

            if (!empty($majorIds)) {
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
