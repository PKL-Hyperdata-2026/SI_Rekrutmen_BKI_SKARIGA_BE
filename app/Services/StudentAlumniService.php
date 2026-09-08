<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentAlumniService
{
    /** @var array<int, string> */
    protected array $sortableColumns = [
        'id',
        'nis',
        'graduation_year',
        'current_position',
        'starting_salary',
        'waiting_time_months',
        'created_at',
    ];

    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StudentAlumni::with(['user', 'major', 'class', 'employmentStatus', 'currentCompany'])
            ->alumni();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('currentCompany', function (Builder $companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['graduation_year']) && $filters['graduation_year'] !== '') {
            $query->where('graduation_year', $filters['graduation_year']);
        }

        if (! empty($filters['major_id'])) {
            $query->where('major_id', $filters['major_id']);
        }

        if (! empty($filters['employment_status_id'])) {
            $query->where('employment_status_id', $filters['employment_status_id']);
        }

        if (! empty($filters['current_company_id'])) {
            $query->where('current_company_id', $filters['current_company_id']);
        }

        $sortBy = isset($filters['sort_by']) && in_array($filters['sort_by'], $this->sortableColumns, true)
            ? $filters['sort_by']
            : 'id';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function show(StudentAlumni $alumni): StudentAlumni
    {
        return $alumni->load(['user', 'major', 'class', 'employmentStatus', 'currentCompany', 'portfolios.category']);
    }

    public function create(array $data, ?int $actorId = null): StudentAlumni
    {
        return DB::transaction(function () use ($data, $actorId) {
            if (empty($data['current_company_id']) && ! empty($data['company_name'])) {
                $company = Company::firstOrCreate(
                    ['name' => trim((string) $data['company_name'])],
                    ['is_active' => true, 'created_by' => $actorId, 'updated_by' => $actorId]
                );
                $data['current_company_id'] = $company->id;
            }

            if (! empty($data['user_id'])) {
                $user = User::where('id', $data['user_id'])->first();

                if (! $user || $user->role !== 'siswa') {
                    throw ValidationException::withMessages([
                        'user_id' => ['Hanya akun dengan role siswa yang dapat di-upgrade menjadi alumni.'],
                    ]);
                }

                $existing = StudentAlumni::withTrashed()->where('user_id', $data['user_id'])->first();

                if ($existing && $existing->graduation_year !== null) {
                    throw ValidationException::withMessages([
                        'user_id' => ['Akun ini sudah terdaftar sebagai alumni.'],
                    ]);
                }

                if ($existing && $existing->trashed()) {
                    $existing->restore();
                }

                $profileData = Arr::except($data, ['full_name', 'phone', 'email', 'company_name']);
                $profileData['created_by'] = $actorId;
                $profileData['updated_by'] = $actorId;

                if ($existing) {
                    $existing->update($profileData);
                    $alumni = $existing;
                } else {
                    $this->assertNisAvailable($data['nis'] ?? null, null, $data['user_id']);
                    $alumni = StudentAlumni::create($profileData);
                }

                $this->syncUser($user, $data, true);

                return $alumni->load(['user', 'major', 'class', 'employmentStatus', 'currentCompany', 'portfolios.category']);
            }

            // Manual alumni creation without existing user account
            $email = $data['email'] ?? (($data['nis'] ?? 'alumni_'.uniqid()) . '@alumni.skariga.sch.id');

            $existingUser = User::withTrashed()->where('email', $email)->first();
            if ($existingUser) {
                if ($existingUser->trashed()) {
                    $existingUser->restore();
                }
                $existingUser->update([
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'] ?? $existingUser->phone,
                    'role' => 'alumni',
                    'is_active' => true,
                    'updated_by' => $actorId,
                ]);
                $user = $existingUser;
            } else {
                $user = User::create([
                    'full_name' => $data['full_name'],
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'password' => bcrypt($data['nis'] ?? 'alumni123'),
                    'role' => 'alumni',
                    'is_active' => true,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            $profileData = Arr::except($data, ['full_name', 'phone', 'email', 'company_name']);
            $profileData['user_id'] = $user->id;
            $profileData['is_active'] = $data['is_active'] ?? true;
            $profileData['created_by'] = $actorId;
            $profileData['updated_by'] = $actorId;

            $existing = ! empty($data['nis'])
                ? StudentAlumni::withTrashed()->where('nis', $data['nis'])->first()
                : null;

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($profileData);
                $alumni = $existing;
            } else {
                $alumni = StudentAlumni::create($profileData);
            }

            return $alumni->load(['user', 'major', 'class', 'employmentStatus', 'currentCompany', 'portfolios.category']);
        });
    }

    public function update(StudentAlumni $alumni, array $data, ?int $actorId = null): StudentAlumni
    {
        return DB::transaction(function () use ($alumni, $data, $actorId) {
            if (empty($data['current_company_id']) && ! empty($data['company_name'])) {
                $company = Company::firstOrCreate(
                    ['name' => trim((string) $data['company_name'])],
                    ['is_active' => true, 'created_by' => $actorId, 'updated_by' => $actorId]
                );
                $data['current_company_id'] = $company->id;
            }

            $profileData = Arr::except($data, ['full_name', 'phone', 'email', 'company_name']);
            $profileData['updated_by'] = $actorId;

            $alumni->update($profileData);

            $user = $alumni->user;

            if ($user) {
                $this->syncUser($user, $data, $alumni->graduation_year !== null);
            }

            return $alumni->fresh(['user', 'major', 'class', 'employmentStatus', 'currentCompany', 'portfolios.category']);
        });
    }

    public function delete(StudentAlumni $alumni, ?int $actorId = null): bool
    {
        return DB::transaction(function () use ($alumni, $actorId) {
            if ($alumni->user) {
                $alumni->user->update(['is_active' => false]);
            }

            $alumni->updated_by = $actorId;
            $alumni->deleted_by = $actorId;
            $alumni->save();

            return $alumni->delete();
        });
    }

    public function getFormOptions(): array
    {
        $companies = Company::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $majors = Major::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $classes = StandardType::byCategory('class')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name']);

        $employmentStatuses = StandardType::byCategory('employment_status')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name']);

        $portfolioTypes = StandardType::byCategory('portfolio_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name']);

        $currentYear = (int) date('Y');
        $graduationYears = range($currentYear - 10, $currentYear);

        $eligibleStudents = StudentAlumni::with(['user', 'class', 'major'])
            ->whereNull('graduation_year')
            ->where('is_active', true)
            ->get()
            ->map(fn (StudentAlumni $student) => [
                'id' => $student->id,
                'userId' => $student->user_id,
                'nis' => $student->nis,
                'fullName' => $student->user?->full_name,
                'email' => $student->user?->email,
                'phone' => $student->user?->phone,
                'classId' => $student->class_id,
                'className' => $student->class?->name,
                'majorId' => $student->major_id,
                'majorName' => $student->major?->name,
            ]);

        return [
            'companies' => $companies,
            'majors' => $majors,
            'classes' => $classes,
            'employment_statuses' => $employmentStatuses,
            'portfolio_types' => $portfolioTypes,
            'graduation_years' => $graduationYears,
            'eligible_students' => $eligibleStudents,
        ];
    }

    /**
     * Create or update a student alumni profile.
     *
     * Handles the unique + softDeletes conflict by restoring a matching
     * soft-deleted record instead of inserting a duplicate row.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveStudentAlumni(array $data, ?int $id = null): StudentAlumni
    {
        return DB::transaction(function () use ($data, $id) {
            $existing = $id ? StudentAlumni::withTrashed()->find($id) : null;

            $hasIdentity = ! empty($data['nis']) || ! empty($data['user_id']);

            $conflict = $hasIdentity
                ? StudentAlumni::whereNull('deleted_at')
                    ->when($id, fn (Builder $query) => $query->where('id', '!=', $id))
                    ->where(function (Builder $query) use ($data) {
                        if (! empty($data['nis'])) {
                            $query->orWhere('nis', $data['nis']);
                        }

                        if (! empty($data['user_id'])) {
                            $query->orWhere('user_id', $data['user_id']);
                        }
                    })
                    ->first()
                : null;

            if ($conflict) {
                throw ValidationException::withMessages([
                    'nis' => ['NIS atau user_id sudah dipakai oleh profil lain yang masih aktif.'],
                ]);
            }

            if (! $existing && ! empty($data['nis'])) {
                $existing = StudentAlumni::withTrashed()
                    ->where('nis', $data['nis'])
                    ->first();
            }

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                $existing->update($data);

                return $existing;
            }

            return StudentAlumni::create($data);
        });
    }

    /**
     * Sync user account fields (full_name, phone) and upgrade/downgrade the
     * role based on the alumni status (graduation_year presence).
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncUser(User $user, array $data, bool $isAlumni): void
    {
        $userUpdate = [];

        if (array_key_exists('full_name', $data) && $data['full_name'] !== null) {
            $userUpdate['full_name'] = $data['full_name'];
        }

        if (array_key_exists('email', $data) && ! empty($data['email'])) {
            $userUpdate['email'] = $data['email'];
        }

        if (array_key_exists('phone', $data) && $data['phone'] !== null) {
            $userUpdate['phone'] = $data['phone'];
        }

        if (array_key_exists('is_active', $data) && $data['is_active'] !== null) {
            $userUpdate['is_active'] = (bool) $data['is_active'];
        }

        if ($isAlumni) {
            $userUpdate['role'] = 'alumni';
        } elseif ($user->role === 'alumni') {
            $userUpdate['role'] = 'siswa';
        }

        if (! empty($userUpdate)) {
            $user->update($userUpdate);
        }
    }

    /**
     * Prevent NIS reuse against soft-deleted records from other users.
     */
    protected function assertNisAvailable(?string $nis, ?int $ignoreId, ?int $ignoreUserId): void
    {
        if (empty($nis)) {
            return;
        }

        $taken = StudentAlumni::withTrashed()
            ->where('nis', $nis)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->when($ignoreUserId, fn (Builder $query) => $query->where('user_id', '!=', $ignoreUserId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'nis' => ['NIS sudah pernah digunakan pada data lain.'],
            ]);
        }
    }
}
