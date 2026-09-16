<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudentJobVacancyService
{
    public function getStudentVacancies(array $filters, int $perPage, string $role): LengthAwarePaginator
    {
        $query = JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors'])
            ->where('is_active', true)
            ->whereHas('status', function (Builder $q): void {
                $q->where('code', 'published');
            })
            ->where(function (Builder $q): void {
                $q->whereNull('deadline')->orWhere('deadline', '>=', now()->toDateString());
            });

        // target_applicant: class_12_only=siswa, alumni_only=alumni, null=bebas
        if ($role === 'siswa') {
            $query->where(function (Builder $q): void {
                $q->whereHas('targetApplicant', fn (Builder $t) => $t->where('code', 'class_12_only'))
                    ->orWhereNull('target_applicant_id');
            });
        } elseif ($role === 'alumni') {
            $query->where(function (Builder $q): void {
                $q->whereHas('targetApplicant', fn (Builder $t) => $t->where('code', 'alumni_only'))
                    ->orWhereNull('target_applicant_id');
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('work_location', 'like', "%{$search}%")
                    ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'like', "%{$search}%"));
            });
        }
        if (! empty($filters['company_id'])) $query->where('company_id', $filters['company_id']);
        if (! empty($filters['job_type_id'])) $query->where('job_type_id', $filters['job_type_id']);
        if (! empty($filters['target_applicant_id'])) $query->where('target_applicant_id', $filters['target_applicant_id']);
        if (! empty($filters['work_location'])) $query->where('work_location', 'like', "%{$filters['work_location']}%");
        if (! empty($filters['major_id'])) {
            $mid = $filters['major_id'];
            $query->whereHas('majors', fn (Builder $m) => $m->where('majors.id', $mid));
        }

        return $query->latest()->paginate($perPage);
    }

    public function getStudentVacancyDetail(string $idOrSlug): JobVacancy
    {
        return JobVacancy::with(['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy'])
            ->where(fn (Builder $q) => $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug))
            ->firstOrFail();
    }

    public function getFormOptionsForStudent(): array
    {
        // hanya opsi dari lowongan published+aktif agar dropdown relevan Figma
        $vacancyIds = JobVacancy::where('is_active', true)
            ->whereHas('status', fn (Builder $q) => $q->where('code', 'published'))
            ->pluck('id');

        $companies = Company::whereIn('id', JobVacancy::whereIn('id', $vacancyIds)->distinct()->pluck('company_id'))
            ->where('is_active', true)->select('id','name','logo_path')->orderBy('name')->get();

        $majors = Major::whereHas('jobVacancies', fn (Builder $q) => $q->whereIn('job_vacancies.id', $vacancyIds))
            ->where('is_active', true)->select('id','code','name')->orderBy('name')->get();
        if ($majors->isEmpty()) {
            $majors = Major::where('is_active', true)->select('id','code','name')->orderBy('name')->get();
        }

        $jobTypes = StandardType::byCategory('job_type')->where('is_active', true)->orderBy('sort_order')->get(['id','code','name']);
        $targets = StandardType::byCategory('target_applicant')->where('is_active', true)->orderBy('sort_order')->get(['id','code','name']);
        $workLocations = JobVacancy::whereIn('id', $vacancyIds)->whereNotNull('work_location')->distinct()->orderBy('work_location')->pluck('work_location');

        return [
            'companies' => $companies,
            'majors' => $majors,
            'jobTypes' => $jobTypes,
            'targetApplicants' => $targets,
            'workLocations' => $workLocations,
        ];
    }

    public function hasStudentApplied(int|string $vacancyId, ?int $studentAlumniId): bool
    {
        if (! $studentAlumniId) {
            return false;
        }

        return JobApplication::where('job_vacancy_id', $vacancyId)
            ->where('student_alumni_id', $studentAlumniId)
            ->exists();
    }

    public function applyToVacancy(JobVacancy $vacancy, int $userId, string $role, ?string $notes = null): JobApplication
    {
        $student = StudentAlumni::where('user_id', $userId)->firstOrFail();
        $vacancy->loadMissing(['status', 'targetApplicant']);

        if (! $vacancy->is_active) throw new HttpException(422, 'Lowongan tidak aktif.');
        if ($vacancy->status && $vacancy->status->code !== 'published') throw new HttpException(422, 'Lowongan belum dibuka.');
        if ($vacancy->deadline && $vacancy->deadline->isPast()) throw new HttpException(422, 'Lowongan sudah melewati batas pendaftaran.');

        $targetCode = $vacancy->targetApplicant?->code;
        if ($targetCode === 'class_12_only' && $role !== 'siswa') throw new HttpException(403, 'Lowongan ini hanya untuk Siswa.');
        if ($targetCode === 'alumni_only' && $role !== 'alumni') throw new HttpException(403, 'Lowongan ini hanya untuk Alumni.');

        if (JobApplication::where('job_vacancy_id', $vacancy->id)->where('student_alumni_id', $student->id)->exists()) {
            throw new HttpException(409, 'Anda sudah melamar lowongan ini.');
        }
        if ($vacancy->quota && JobApplication::where('job_vacancy_id', $vacancy->id)->count() >= $vacancy->quota) {
            throw new HttpException(422, 'Kuota lowongan sudah penuh.');
        }

        return DB::transaction(function () use ($vacancy, $student, $userId, $notes): JobApplication {
            $pending = StandardType::byCategory('job_application_status')->where('code', 'pending')->first();
            return JobApplication::create([
                'job_vacancy_id' => $vacancy->id,
                'student_alumni_id' => $student->id,
                'status_id' => $pending?->id,
                'applied_at' => now(),
                'notes' => $notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }
}