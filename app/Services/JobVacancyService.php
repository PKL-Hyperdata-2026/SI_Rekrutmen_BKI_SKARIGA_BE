<?php

namespace App\Services;

use App\Models\JobVacancy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class JobVacancyService
{
    public function generateUniqueSlug(string $title, int $companyId): string
    {
        $baseSlug = Str::slug($title);
        $slug = "{$baseSlug}-{$companyId}-" . Str::random(5);

        while (JobVacancy::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$companyId}-" . Str::random(6);
        }

        return $slug;
    }

    public function createJobVacancy(array $data, ?int $userId = null): JobVacancy
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], $data['company_id']);
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            $majorIds = $data['major_ids'] ?? [];
            unset($data['major_ids']);

            $vacancy = JobVacancy::create($data);

            if (!empty($majorIds)) {
                $vacancy->majors()->sync($majorIds);
            }

            return $vacancy->load(['company', 'jobType', 'status', 'targetApplicant', 'majors']);
        });
    }

    public function updateJobVacancy(JobVacancy $vacancy, array $data, ?int $userId = null): JobVacancy
    {
        return DB::transaction(function () use ($vacancy, $data, $userId) {
            if (isset($data['title']) && $data['title'] !== $vacancy->title) {
                $companyId = $data['company_id'] ?? $vacancy->company_id;
                $data['slug'] = $this->generateUniqueSlug($data['title'], $companyId);
            }

            $data['updated_by'] = $userId;

            if (array_key_exists('major_ids', $data)) {
                $vacancy->majors()->sync($data['major_ids'] ?? []);
                unset($data['major_ids']);
            }

            $vacancy->update($data);

            return $vacancy->fresh(['company', 'jobType', 'status', 'targetApplicant', 'majors']);
        });
    }

    public function deleteJobVacancy(JobVacancy $vacancy, ?int $userId = null): bool
    {
        $vacancy->updated_by = $userId;
        $vacancy->deleted_by = $userId;
        $vacancy->save();

        return $vacancy->delete();
    }
}
