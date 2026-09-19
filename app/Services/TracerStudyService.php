<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobPlacement;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\TracerStudy;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TracerStudyService
{
    /**
     * Ambil data tracer study milik alumni yang sedang login.
     */
    public function getAlumniTracerStudy(User $user): ?TracerStudy
    {
        $alumni = $this->resolveAlumni($user);

        return TracerStudy::where('student_alumni_id', $alumni->id)->first();
    }

    /**
     * Simpan atau update data tracer study alumni (self-service).
     *
     * @param  array<string, mixed>  $data
     */
    public function submitTracerStudy(User $user, array $data): TracerStudy
    {
        $alumni = $this->resolveAlumni($user);

        return DB::transaction(function () use ($user, $alumni, $data): TracerStudy {
            $payload = $this->buildPayload($alumni->id, $data, $user->id);

            $tracer = TracerStudy::where('student_alumni_id', $alumni->id)->first();

            if ($tracer) {
                $tracer->update($payload);

                return $tracer->fresh();
            }

            $payload['created_by'] = $user->id;

            return TracerStudy::create($payload);
        });
    }

    private function resolveAlumni(User $user): StudentAlumni
    {
        $alumni = StudentAlumni::where('user_id', $user->id)->first();

        if (! $alumni) {
            if ($user->role !== 'alumni') {
                throw new NotFoundHttpException('Data alumni tidak ditemukan untuk pengguna ini.');
            }

            $defaultMajor = Major::where('is_active', true)->first();
            $defaultClass = StandardType::byCategory('class')->where('is_active', true)->first();

            $alumni = StudentAlumni::create([
                'user_id' => $user->id,
                'major_id' => $defaultMajor?->id ?? 1,
                'class_id' => $defaultClass?->id,
                'nis' => null,
                'graduation_year' => (int) date('Y'),
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        } elseif (empty($alumni->graduation_year) && $user->role === 'alumni') {
            $alumni->update([
                'graduation_year' => (int) date('Y'),
                'updated_by' => $user->id,
            ]);
        }

        return $alumni;
    }

    /**
     * List tracer studies untuk Admin dengan pencarian, filter, dan pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function indexAdmin(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = TracerStudy::with([
            'studentAlumni.user',
            'studentAlumni.major',
            'studentAlumni.class',
            'studentAlumni.currentCompany',
            'studentAlumni.jobPlacements.company',
            'studentAlumni.jobPlacements.placementStatus',
        ]);

        // 1. Search Query
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $lowerSearch = strtolower($search);
                $q->whereRaw('LOWER(company_name) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(job_title) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(company_sector) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(job_location) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(university_name) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(study_program) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(business_name) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereHas('studentAlumni', function (Builder $saQuery) use ($lowerSearch) {
                        $saQuery->whereRaw('LOWER(nis) LIKE ?', ["%{$lowerSearch}%"])
                            ->orWhereHas('user', function (Builder $uQuery) use ($lowerSearch) {
                                $uQuery->whereRaw('LOWER(full_name) LIKE ?', ["%{$lowerSearch}%"])
                                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$lowerSearch}%"]);
                            });
                    });
            });
        }

        // 2. Filter: Status Karir
        if (! empty($filters['career_status']) && $filters['career_status'] !== 'all') {
            $query->where('career_status', $filters['career_status']);
        }

        // 3. Filter: Jurusan (Major)
        if (! empty($filters['major_id']) && $filters['major_id'] !== 'all') {
            $query->whereHas('studentAlumni', function (Builder $q) use ($filters) {
                $q->where('major_id', $filters['major_id']);
            });
        }

        // 4. Filter: Angkatan / Tahun Lulus (Graduation Year)
        if (! empty($filters['graduation_year']) && $filters['graduation_year'] !== 'all') {
            $query->whereHas('studentAlumni', function (Builder $q) use ($filters) {
                $q->where('graduation_year', (int) $filters['graduation_year']);
            });
        }

        // 5. Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'name') {
            $query->join('students_alumni', 'students_alumni.id', '=', 'tracer_studies.student_alumni_id')
                ->join('users', 'users.id', '=', 'students_alumni.user_id')
                ->orderBy('users.full_name', $sortDir)
                ->select('tracer_studies.*');
        } elseif ($sortBy === 'salary') {
            $query->orderByRaw("COALESCE(tracer_studies.maximum_salary, tracer_studies.minimum_salary, 0) {$sortDir}");
        } elseif ($sortBy === 'start_date') {
            $query->orderBy('start_date', $sortDir);
        } else {
            $query->orderBy('tracer_studies.created_at', $sortDir);
        }

        return $query->paginate($perPage);
    }

    /**
     * Hitung metrik 5 Card untuk Dashboard Tracer Study Admin.
     *
     * @return array<string, int>
     */
    public function getMetrics(): array
    {
        $totalAlumni = StudentAlumni::alumni()->count();
        $bekerja = TracerStudy::where('career_status', 'bekerja')->count();
        $kuliah = TracerStudy::where('career_status', 'lanjut_studi')->count();
        $wirausaha = TracerStudy::where('career_status', 'wirausaha')->count();
        $mencariKerja = TracerStudy::where('career_status', 'mencari_pekerjaan')->count();

        return [
            'total_alumni' => $totalAlumni,
            'bekerja' => $bekerja,
            'kuliah' => $kuliah,
            'wirausaha' => $wirausaha,
            'mencari_kerja' => $mencariKerja,
        ];
    }

    public function show(TracerStudy $tracerStudy): TracerStudy
    {
        return $tracerStudy->load([
            'studentAlumni.user',
            'studentAlumni.major',
            'studentAlumni.class',
            'studentAlumni.jobPlacements.company',
            'studentAlumni.jobPlacements.placementStatus',
        ]);
    }

    /**
     * Ambil opsi filter & form Tracer Study Admin.
     *
     * @return array<string, mixed>
     */
    public function getAdminFormOptions(): array
    {
        $majors = Major::where('is_active', true)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        $graduationYears = StudentAlumni::alumni()
            ->whereNotNull('graduation_year')
            ->distinct()
            ->orderBy('graduation_year', 'desc')
            ->pluck('graduation_year');

        $careerStatuses = [
            ['value' => 'bekerja', 'label' => 'Bekerja'],
            ['value' => 'lanjut_studi', 'label' => 'Kuliah / Lanjut Studi'],
            ['value' => 'wirausaha', 'label' => 'Wirausaha'],
            ['value' => 'mencari_pekerjaan', 'label' => 'Mencari Kerja'],
        ];

        // Daftar alumni yang belum mengisi tracer study untuk opsi "+ Tambah Data"
        $availableAlumni = StudentAlumni::alumni()
            ->with(['user', 'major', 'class'])
            ->whereDoesntHave('tracerStudy')
            ->get()
            ->map(function ($alumni) {
                $majorName = $alumni->major?->name ?? '';
                $className = $alumni->class?->name ?? '';
                $label = $alumni->user?->full_name.' ('.$alumni->nis.($majorName ? ' - '.$majorName : '').')';

                return [
                    'id' => encrypt($alumni->id),
                    'nis' => $alumni->nis,
                    'fullName' => $alumni->user?->full_name,
                    'major' => $majorName,
                    'class' => $className,
                    'year' => $alumni->graduation_year,
                    'label' => $label,
                ];
            });

        return [
            'majors' => $majors,
            'graduation_years' => $graduationYears,
            'career_statuses' => $careerStatuses,
            'available_alumni' => $availableAlumni,
        ];
    }

    /**
     * Admin menambah data tracer study alumni.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAdmin(array $data, int $adminUserId): TracerStudy
    {
        return DB::transaction(function () use ($data, $adminUserId): TracerStudy {
            $studentAlumniId = (int) $data['student_alumni_id'];

            $payload = $this->buildPayload($studentAlumniId, $data, $adminUserId);
            $payload['created_by'] = $adminUserId;

            // Jika record sudah ada (misal soft deleted atau update), update or create
            $existing = TracerStudy::withTrashed()->where('student_alumni_id', $studentAlumniId)->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($payload);

                return $existing->fresh(['studentAlumni.user', 'studentAlumni.major', 'studentAlumni.class']);
            }

            $tracer = TracerStudy::create($payload);

            return $tracer->load(['studentAlumni.user', 'studentAlumni.major', 'studentAlumni.class']);
        });
    }

    /**
     * Admin memperbarui data tracer study alumni.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAdmin(TracerStudy $tracerStudy, array $data, int $adminUserId): TracerStudy
    {
        return DB::transaction(function () use ($tracerStudy, $data, $adminUserId): TracerStudy {
            $payload = $this->buildPayload($tracerStudy->student_alumni_id, $data, $adminUserId);

            $tracerStudy->update($payload);

            return $tracerStudy->fresh(['studentAlumni.user', 'studentAlumni.major', 'studentAlumni.class']);
        });
    }

    /**
     * Admin menghapus (soft delete) data tracer study alumni.
     */
    public function deleteAdmin(TracerStudy $tracerStudy, int $adminUserId): bool
    {
        $tracerStudy->deleted_by = $adminUserId;
        $tracerStudy->save();

        return (bool) $tracerStudy->delete();
    }

    /**
     * Sinkronisasi data tracer study dari data penempatan (JobPlacement) dan profil alumni.
     *
     * @return int Jumlah data yang berhasil disinkronkan.
     */
    public function syncFromPlacements(int $adminUserId): int
    {
        return DB::transaction(function () use ($adminUserId): int {
            $syncedCount = 0;

            // Cari alumni yang belum punya tracer study
            $alumniWithoutTracer = StudentAlumni::alumni()
                ->whereDoesntHave('tracerStudy')
                ->with(['currentCompany.industry', 'jobPlacements.company.industry', 'jobPlacements.jobApplication.vacancy'])
                ->get();

            foreach ($alumniWithoutTracer as $alumni) {
                /** @var JobPlacement|null $placement */
                $placement = $alumni->jobPlacements->sortByDesc('id')->first();

                if ($placement) {
                    $companyName = $placement->company?->name ?? 'Perusahaan Mitra';
                    $companySector = $placement->company?->industry?->name ?? 'Sektor Industri';
                    $jobTitle = $placement->jobApplication?->vacancy?->title ?? $alumni->current_position ?? 'Staf / Tenaga Kerja';
                    $jobLocation = $placement->company?->city ?? $placement->company?->address ?? 'Indonesia';

                    TracerStudy::create([
                        'student_alumni_id' => $alumni->id,
                        'career_status' => 'bekerja',
                        'company_name' => $companyName,
                        'company_sector' => $companySector,
                        'job_title' => $jobTitle,
                        'job_location' => $jobLocation,
                        'minimum_salary' => $alumni->starting_salary ? (int) $alumni->starting_salary : null,
                        'maximum_salary' => $alumni->starting_salary ? (int) $alumni->starting_salary : null,
                        'waiting_period' => $alumni->waiting_time_months ? $alumni->waiting_time_months.' Bulan' : '< 1 Bulan',
                        'accepted_date' => $placement->accepted_date,
                        'start_date' => $placement->start_date,
                        'created_by' => $adminUserId,
                        'updated_by' => $adminUserId,
                    ]);

                    $syncedCount++;
                } elseif ($alumni->current_company_id || $alumni->current_position) {
                    TracerStudy::create([
                        'student_alumni_id' => $alumni->id,
                        'career_status' => 'bekerja',
                        'company_name' => $alumni->currentCompany?->name ?? 'Perusahaan Mitra',
                        'company_sector' => $alumni->currentCompany?->industry?->name ?? 'Sektor Industri',
                        'job_title' => $alumni->current_position ?? 'Staf / Tenaga Kerja',
                        'job_location' => $alumni->currentCompany?->city ?? 'Indonesia',
                        'minimum_salary' => $alumni->starting_salary ? (int) $alumni->starting_salary : null,
                        'maximum_salary' => $alumni->starting_salary ? (int) $alumni->starting_salary : null,
                        'waiting_period' => $alumni->waiting_time_months ? $alumni->waiting_time_months.' Bulan' : '< 1 Bulan',
                        'accepted_date' => null,
                        'start_date' => null,
                        'created_by' => $adminUserId,
                        'updated_by' => $adminUserId,
                    ]);

                    $syncedCount++;
                }
            }

            return $syncedCount;
        });
    }

    /**
     * Membangun payload atribut Tracer Study dengan reset null pada field status lain.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildPayload(int $studentAlumniId, array $data, int $userId): array
    {
        $careerStatus = (string) $data['career_status'];

        $payload = [
            'student_alumni_id' => $studentAlumniId,
            'career_status' => $careerStatus,

            // Bekerja
            'company_name' => null,
            'company_sector' => null,
            'job_title' => null,
            'job_location' => null,
            'minimum_salary' => null,
            'maximum_salary' => null,
            'waiting_period' => null,
            'accepted_date' => null,
            'start_date' => null,

            // Wirausaha
            'business_name' => null,
            'business_address' => null,
            'instagram_handle' => null,
            'average_income' => null,
            'business_field' => null,
            'business_start_date' => null,

            // Lanjut Studi
            'university_name' => null,
            'study_program' => null,

            'updated_by' => $userId,
        ];

        if ($careerStatus === 'bekerja') {
            $payload['company_name'] = $data['company_name'] ?? null;
            $payload['company_sector'] = $data['company_sector'] ?? null;
            $payload['job_title'] = $data['job_title'] ?? null;
            $payload['job_location'] = $data['job_location'] ?? null;
            $payload['minimum_salary'] = isset($data['minimum_salary']) ? (int) $data['minimum_salary'] : null;
            $payload['maximum_salary'] = isset($data['maximum_salary']) ? (int) $data['maximum_salary'] : null;
            $payload['waiting_period'] = $data['waiting_period'] ?? null;
            $payload['accepted_date'] = $data['accepted_date'] ?? null;
            $payload['start_date'] = $data['start_date'] ?? null;
        } elseif ($careerStatus === 'wirausaha') {
            $payload['business_name'] = $data['business_name'] ?? null;
            $payload['business_address'] = $data['business_address'] ?? null;
            $payload['job_location'] = $data['job_location'] ?? null;
            $payload['instagram_handle'] = $data['instagram_handle'] ?? null;
            $payload['average_income'] = $data['average_income'] ?? null;
            $payload['business_field'] = $data['business_field'] ?? null;
            $payload['business_start_date'] = $data['business_start_date'] ?? null;
        } elseif ($careerStatus === 'lanjut_studi') {
            $payload['university_name'] = $data['university_name'] ?? null;
            $payload['study_program'] = $data['study_program'] ?? null;
            $payload['company_sector'] = $data['company_sector'] ?? null;
            $payload['job_location'] = $data['job_location'] ?? null;
        }

        return $payload;
    }
}
