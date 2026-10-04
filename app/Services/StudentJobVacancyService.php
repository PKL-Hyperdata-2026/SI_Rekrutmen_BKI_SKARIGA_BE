<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudentJobVacancyService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected ActivityScoreService $activityScoreService
    ) {}

    public function getStudentVacancies(
        array $filters,
        int $perPage,
        string $role,
        ?int $studentAlumniId = null,
        ?int $studentMajorId = null
    ): LengthAwarePaginator {
        $relations = ['company', 'jobType', 'status', 'targetApplicant', 'majors'];
        if ($studentAlumniId !== null) {
            $relations['jobApplications'] = fn (HasMany $q) => $q->where('student_alumni_id', $studentAlumniId);
        }

        $query = $this->applyStudentVisibility(
            JobVacancy::with($relations),
            $role,
            $studentMajorId
        );

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('work_location', 'like', "%{$search}%")
                    ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'like', "%{$search}%"));
            });
        }
        if (! empty($filters['department_id'])) {
            $did = $filters['department_id'];
            $query->whereHas('majors.department', fn (Builder $d) => $d->where('departments.id', $did));
        }
        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        if (! empty($filters['job_type_id'])) {
            $query->where('job_type_id', $filters['job_type_id']);
        }
        if (! empty($filters['target_applicant_id'])) {
            $query->where('target_applicant_id', $filters['target_applicant_id']);
        }
        if (! empty($filters['work_location'])) {
            $query->where('work_location', 'like', "%{$filters['work_location']}%");
        }
        if (! empty($filters['major_id'])) {
            $mid = $filters['major_id'];
            $query->whereHas('majors', fn (Builder $m) => $m->where('majors.id', $mid));
        }

        $direction = strtolower((string) ($filters['sort_direction'] ?? 'asc'));
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $orderBy = (string) ($filters['order_by'] ?? 'id');
        if (! in_array($orderBy, ['id', 'created_at', 'title', 'deadline'], true)) {
            $orderBy = 'id';
        }

        return $query->orderBy($orderBy, $direction)->paginate($perPage);
    }

    public function getStudentVacancyDetail(string $idOrSlug, string $role, ?int $studentAlumniId = null, ?int $studentMajorId = null): JobVacancy
    {
        $relations = ['company', 'jobType', 'status', 'targetApplicant', 'majors', 'createdBy'];
        if ($studentAlumniId !== null) {
            $relations['jobApplications'] = fn (HasMany $q) => $q->where('student_alumni_id', $studentAlumniId);
        }

        return $this->applyStudentVisibility(
            JobVacancy::with($relations),
            $role,
            $studentMajorId
        )
            ->where(fn (Builder $q) => $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug))
            ->firstOrFail();
    }

    private function applyStudentVisibility(Builder $query, string $role, ?int $studentMajorId): Builder
    {
        $query->where('is_active', true)
            ->whereHas('status', function (Builder $q): void {
                $q->where('code', 'published');
            })
            ->where(function (Builder $q): void {
                $q->whereNull('deadline')->orWhere('deadline', '>=', now()->toDateString());
            });

        if ($role === 'siswa') {
            $query->where(function (Builder $q): void {
                $q->whereHas('targetApplicant', fn (Builder $t) => $t->whereIn('code', ['class_12_only', 'class_12_and_alumni']))
                    ->orWhereNull('target_applicant_id');
            });
        } elseif ($role === 'alumni') {
            $query->where(function (Builder $q): void {
                $q->whereHas('targetApplicant', fn (Builder $t) => $t->whereIn('code', ['alumni_only', 'class_12_and_alumni']))
                    ->orWhereNull('target_applicant_id');
            });
        }

        if ($studentMajorId !== null) {
            $query->where(function (Builder $q) use ($studentMajorId): void {
                $q->doesntHave('majors')
                    ->orWhereHas('majors', fn (Builder $m) => $m->where('majors.id', $studentMajorId));
            });
        } else {
            $query->doesntHave('majors');
        }

        return $query;
    }

    public function getFormOptionsForStudent(string $role, ?int $studentMajorId = null): array
    {
        $vacanciesQuery = JobVacancy::where('is_active', true)
            ->whereHas('status', fn (Builder $q) => $q->where('code', 'published'))
            ->where(function (Builder $q): void {
                $q->whereNull('deadline')->orWhere('deadline', '>=', now()->toDateString());
            });

        if ($role === 'siswa') {
            $vacanciesQuery->where(function (Builder $q): void {
                $q->whereHas('targetApplicant', fn (Builder $t) => $t->whereIn('code', ['class_12_only', 'class_12_and_alumni']))
                    ->orWhereNull('target_applicant_id');
            });
        } elseif ($role === 'alumni') {
            $vacanciesQuery->where(function (Builder $q): void {
                $q->whereHas('targetApplicant', fn (Builder $t) => $t->whereIn('code', ['alumni_only', 'class_12_and_alumni']))
                    ->orWhereNull('target_applicant_id');
            });
        }

        if ($studentMajorId !== null) {
            $vacanciesQuery->where(function (Builder $q) use ($studentMajorId): void {
                $q->doesntHave('majors')
                    ->orWhereHas('majors', fn (Builder $m) => $m->where('majors.id', $studentMajorId));
            });
        } else {
            $vacanciesQuery->doesntHave('majors');
        }

        $vacancyIds = $vacanciesQuery->pluck('id');

        $departments = Department::whereHas('majors.jobVacancies', fn (Builder $q) => $q->whereIn('job_vacancies.id', $vacancyIds))
            ->where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $deptEncryptedMap = [];
        foreach ($departments as $dept) {
            $deptEncryptedMap[$dept->id] = encrypt((string) $dept->id);
        }

        $departmentsTransformed = $departments->map(fn (Department $d): array => [
            'id' => $deptEncryptedMap[$d->id],
            'code' => $d->code,
            'name' => $d->name,
        ])->all();

        $majors = Major::whereHas('jobVacancies', fn (Builder $q) => $q->whereIn('job_vacancies.id', $vacancyIds))
            ->where('is_active', true)
            ->select('id', 'code', 'name', 'department_id')
            ->orderBy('name')
            ->get()
            ->map(fn (Major $m): array => [
                'id' => encrypt((string) $m->id),
                'code' => $m->code,
                'name' => $m->name,
                'department_id' => $deptEncryptedMap[$m->department_id] ?? ($m->department_id ? encrypt((string) $m->department_id) : null),
            ])->all();

        $targetIds = JobVacancy::whereIn('id', $vacancyIds)
            ->whereNotNull('target_applicant_id')
            ->distinct()
            ->pluck('target_applicant_id');

        $targets = StandardType::whereIn('id', $targetIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name']);

        $workLocations = JobVacancy::whereIn('id', $vacancyIds)
            ->whereNotNull('work_location')
            ->where('work_location', '!=', '')
            ->distinct()
            ->orderBy('work_location')
            ->pluck('work_location');

        $companies = Company::whereIn('id', JobVacancy::whereIn('id', $vacancyIds)->distinct()->pluck('company_id'))
            ->where('is_active', true)
            ->select('id', 'name', 'logo_path')
            ->orderBy('name')
            ->get();

        return [
            'departments' => $departmentsTransformed,
            'companies' => $companies,
            'majors' => $majors,
            'jobTypes' => [],
            'targetApplicants' => $targets,
            'workLocations' => $workLocations,
        ];
    }

    public function applyToVacancy(JobVacancy $vacancy, int $userId, string $role, ?string $notes = null): JobApplication
    {
        $student = StudentAlumni::where('user_id', $userId)->firstOrFail();
        $vacancy->loadMissing(['status', 'targetApplicant', 'majors']);

        if (! $vacancy->is_active) {
            throw new HttpException(422, 'Lowongan tidak aktif.');
        }
        if ($vacancy->status?->code !== 'published') {
            throw new HttpException(422, 'Lowongan belum dibuka.');
        }
        if ($vacancy->deadline && $vacancy->deadline->toDateString() < now()->toDateString()) {
            throw new HttpException(422, 'Lowongan sudah melewati batas pendaftaran.');
        }

        $targetCode = $vacancy->targetApplicant?->code;
        if ($targetCode === 'class_12_only' && $role !== 'siswa') {
            throw new HttpException(403, 'Lowongan ini hanya untuk Siswa.');
        }
        if ($targetCode === 'alumni_only' && $role !== 'alumni') {
            throw new HttpException(403, 'Lowongan ini hanya untuk Alumni.');
        }

        if ($vacancy->majors->isNotEmpty()) {
            $allowedMajorIds = $vacancy->majors->pluck('id')->all();
            if (! in_array($student->major_id, $allowedMajorIds, true)) {
                throw new HttpException(403, 'Jurusan Anda tidak memenuhi kualifikasi lowongan ini.');
            }
        }

        if (JobApplication::where('job_vacancy_id', $vacancy->id)->where('student_alumni_id', $student->id)->exists()) {
            throw new HttpException(409, 'Anda sudah melamar lowongan ini.');
        }
        if ($vacancy->quota && JobApplication::where('job_vacancy_id', $vacancy->id)->count() >= $vacancy->quota) {
            throw new HttpException(422, 'Kuota lowongan sudah penuh.');
        }

        return DB::transaction(function () use ($vacancy, $student, $userId, $notes): JobApplication {
            $pending = StandardType::byCategory('job_application_status')->where('code', 'pending')->first();
            $application = JobApplication::create([
                'job_vacancy_id' => $vacancy->id,
                'student_alumni_id' => $student->id,
                'status_id' => $pending?->id,
                'applied_at' => now(),
                'notes' => $notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $hrdUserId = $vacancy->company?->user_id ?? $vacancy->created_by;
            if ($hrdUserId) {
                $studentUser = $student->user ?? User::find($userId);
                $applicantName = $studentUser?->full_name ?? 'Pelamar Baru';
                $vacancyTitle = $vacancy->title ?: ($vacancy->position ?? 'Lowongan');
                $this->notificationService->send(
                    $hrdUserId,
                    'job_application',
                    'Lamaran Baru: '.$vacancyTitle,
                    "{$applicantName} telah mengirimkan lamaran pekerjaan untuk posisi {$vacancy->position}.",
                    [
                        'job_vacancy_id' => $vacancy->id,
                        'job_application_id' => $application->id,
                        'applicant_name' => $applicantName,
                        'position' => $vacancy->position,
                    ],
                    true
                );
            }

            $this->activityScoreService->logActivity(
                $userId,
                'apply_vacancy',
                'recruitment',
                JobApplication::class,
                $application->id,
                ['vacancy_title' => $vacancy->title, 'position' => $vacancy->position]
            );

            return $application->load(['jobVacancy.company', 'status']);
        });
    }

    public function getMonthlyTrends(int $userId): array
    {
        $student = StudentAlumni::where('user_id', $userId)->first();
        $studentId = $student?->id;

        $now = Carbon::now();
        $rangeStart = $now->copy()->subMonths(5)->startOfMonth();
        $rangeEnd = $now->copy()->endOfMonth();

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $vacMonthExpr = $isSqlite
            ? "strftime('%Y-%m', created_at)"
            : "to_char(created_at, 'YYYY-MM')";

        $vacancies = JobVacancy::query()
            ->where('is_active', true)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->selectRaw("{$vacMonthExpr} as ym, count(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->all();

        $appMonthExpr = $isSqlite
            ? "strftime('%Y-%m', COALESCE(applied_at, created_at))"
            : "to_char(COALESCE(applied_at, created_at), 'YYYY-MM')";

        $myApplications = [];
        if ($studentId) {
            $myApplications = JobApplication::query()
                ->where('student_alumni_id', $studentId)
                ->where(function ($q) use ($rangeStart, $rangeEnd) {
                    $q->whereBetween('applied_at', [$rangeStart, $rangeEnd])
                        ->orWhere(function ($sub) use ($rangeStart, $rangeEnd) {
                            $sub->whereNull('applied_at')
                                ->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                        });
                })
                ->selectRaw("{$appMonthExpr} as ym, count(*) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym')
                ->all();
        }

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $targetMonth = $now->copy()->subMonths($i);
            $ymKey = $targetMonth->format('Y-m');

            $months[] = [
                'month' => $monthNames[$targetMonth->month] ?? $targetMonth->format('M'),
                'lowongan' => (int) ($vacancies[$ymKey] ?? 0),
                'melamar' => (int) ($myApplications[$ymKey] ?? 0),
            ];
        }

        return $months;
    }
}
