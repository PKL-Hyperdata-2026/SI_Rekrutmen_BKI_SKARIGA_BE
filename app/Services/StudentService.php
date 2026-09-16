<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\StudentPortfolio;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentService
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

    public function getStudents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (! empty($filters['for_select'])) {
            return $this->selectOptions($filters, $perPage);
        }

        $query = StudentAlumni::with([
            'user',
            'class',
            'major',
            'employmentStatus',
            'currentCompany',
            'portfolios.category',
        ])->whereHas('user', function (Builder $userQuery) {
            $userQuery->where('role', 'siswa');
        });

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('currentCompany', function (Builder $companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('major', function (Builder $majorQuery) use ($search) {
                        $majorQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('major', function (Builder $majorQuery) use ($filters) {
                $majorQuery->where('department_id', $filters['department_id']);
            });
        }

        if (! empty($filters['major_id'])) {
            $query->where('major_id', $filters['major_id']);
        }

        if (! empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (! empty($filters['employment_status_id'])) {
            $query->where('employment_status_id', $filters['employment_status_id']);
        }

        if (isset($filters['graduation_year']) && $filters['graduation_year'] !== '') {
            $query->where('graduation_year', $filters['graduation_year']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = in_array($filters['sort_by'] ?? 'id', $this->sortableColumns, true)
            ? ($filters['sort_by'] ?? 'id')
            : 'id';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function show(StudentAlumni $student): StudentAlumni
    {
        return $student->load([
            'user',
            'class',
            'major',
            'employmentStatus',
            'currentCompany',
            'portfolios.category',
        ]);
    }

    /**
     * Paginated lightweight options for async selects.
     * With the eligible flag, only active students without
     * a graduation year are returned (alumni upgrade candidates).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array{value: mixed, label: string, extra: array<string, mixed>}>
     */
    protected function selectOptions(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = StudentAlumni::query()
            ->with([
                'user:id,full_name,email,phone',
                'class:id,name',
                'major:id,name',
            ])
            ->whereHas('user', function (Builder $userQuery) {
                $userQuery->where('role', 'siswa');
            })
            ->where('students_alumni.is_active', true)
            ->select('students_alumni.*');

        if (! empty($filters['eligible'])) {
            $query->student();
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('students_alumni.nis', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $query->join('users', 'users.id', '=', 'students_alumni.user_id')
            ->orderBy('users.full_name');

        return $query->paginate($perPage)->through(
            fn (StudentAlumni $student): array => [
                'value' => $student->user_id,
                'label' => $this->buildSelectLabel($student),
                'extra' => [
                    'studentId' => $student->id,
                    'userId' => $student->user_id,
                    'nis' => $student->nis,
                    'fullName' => $student->user?->full_name,
                    'email' => $student->user?->email,
                    'phone' => $student->user?->phone,
                    'classId' => $student->class_id,
                    'className' => $student->class?->name,
                    'majorId' => $student->major_id,
                    'majorName' => $student->major?->name,
                ],
            ]
        );
    }

    protected function buildSelectLabel(StudentAlumni $student): string
    {
        $label = trim(($student->nis ?? '').' - '.($student->user?->full_name ?? ''));
        $context = $student->class?->name ?? $student->major?->name;

        return $context ? "{$label} ({$context})" : $label;
    }

    public function getStudentById(int $id): StudentAlumni
    {
        return StudentAlumni::with([
            'user',
            'class',
            'major',
            'employmentStatus',
            'currentCompany',
            'portfolios.category',
        ])->findOrFail($id);
    }

    public function getFormOptions(): array
    {
        $companies = Company::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $departments = \App\Models\Department::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $majors = Major::where('is_active', true)
            ->select('id', 'department_id', 'code', 'name')
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
        $graduationYears = range($currentYear - 5, $currentYear + 2);

        return [
            'companies' => $companies,
            'departments' => $departments,
            'majors' => $majors,
            'classes' => $classes,
            'employment_statuses' => $employmentStatuses,
            'portfolio_types' => $portfolioTypes,
            'graduation_years' => $graduationYears,
        ];
    }

    public function createStudent(array $data, ?int $authUserId = null): StudentAlumni
    {
        return DB::transaction(function () use ($data, $authUserId) {
            $userData = [
                'full_name'  => $data['full_name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'] ?? null,
                'password'   => ! empty($data['password']) ? $data['password'] : ($data['nis'] ?? 'siswa123'),
                'role'       => 'siswa',
                'is_active'  => $data['is_active'] ?? true,
                'created_by' => $authUserId,
                'updated_by' => $authUserId,
            ];

            $user = User::create($userData);

            $socialMedia = null;
            if (! empty($data['social_media'])) {
                $socialMedia = is_array($data['social_media'])
                    ? $data['social_media']
                    : ['profile_url' => $data['social_media']];
            }

            $studentData = [
                'user_id'              => $user->id,
                'nis'                  => $data['nis'],
                'class_id'             => $data['class_id'],
                'major_id'             => $data['major_id'],
                'employment_status_id' => $data['employment_status_id'] ?? null,
                'graduation_year'      => $data['graduation_year'] ?? null,
                'social_media'         => $socialMedia,
                'current_company_id'   => $data['current_company_id'] ?? null,
                'current_position'     => $data['current_position'] ?? null,
                'starting_salary'      => $data['starting_salary'] ?? null,
                'waiting_time_months'  => $data['waiting_time_months'] ?? null,
                'is_active'            => $data['is_active'] ?? true,
                'created_by'           => $authUserId,
                'updated_by'           => $authUserId,
            ];

            $student = StudentAlumni::create($studentData);

            return $student->load([
                'user',
                'class',
                'major',
                'employmentStatus',
                'currentCompany',
                'portfolios.category',
            ]);
        });
    }

    public function updateStudent(StudentAlumni $student, array $data, ?int $authUserId = null): StudentAlumni
    {
        return DB::transaction(function () use ($student, $data, $authUserId) {
            if ($student->user_id && $student->user) {
                $userUpdates = [
                    'full_name'  => $data['full_name'] ?? $student->user->full_name,
                    'email'      => $data['email'] ?? $student->user->email,
                    'phone'      => $data['phone'] ?? $student->user->phone,
                    'updated_by' => $authUserId,
                ];

                if (! empty($data['password'])) {
                    $userUpdates['password'] = $data['password'];
                }

                if (isset($data['is_active'])) {
                    $userUpdates['is_active'] = $data['is_active'];
                }

                $student->user->update($userUpdates);
            }

            $studentUpdates = [
                'updated_by' => $authUserId,
            ];

            $allowedFields = [
                'nis',
                'class_id',
                'major_id',
                'employment_status_id',
                'graduation_year',
                'current_company_id',
                'current_position',
                'starting_salary',
                'waiting_time_months',
                'is_active',
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $studentUpdates[$field] = $data[$field];
                }
            }

            if (array_key_exists('social_media', $data)) {
                $studentUpdates['social_media'] = is_array($data['social_media'])
                    ? $data['social_media']
                    : ($data['social_media'] ? ['profile_url' => $data['social_media']] : null);
            }

            $student->update($studentUpdates);

            return $student->fresh([
                'user',
                'class',
                'major',
                'employmentStatus',
                'currentCompany',
                'portfolios.category',
            ]);
        });
    }

    public function deleteStudent(StudentAlumni $student, ?int $authUserId = null): bool
    {
        return DB::transaction(function () use ($student, $authUserId) {
            $student->updated_by = $authUserId;
            $student->deleted_by = $authUserId;
            $student->save();

            if ($student->user) {
                $student->user->is_active = false;
                $student->user->updated_by = $authUserId;
                $student->user->deleted_by = $authUserId;
                $student->user->save();
                $student->user->delete();
            }

            return (bool) $student->delete();
        });
    }

    public function uploadPortfolio(StudentAlumni $student, array $data, UploadedFile $file, ?int $authUserId = null): StudentPortfolio
    {
        return DB::transaction(function () use ($student, $data, $file, $authUserId) {
            $filePath = $file->store('portfolios', 'public');

            $portfolio = StudentPortfolio::create([
                'student_alumni_id' => $student->id,
                'category_id'       => $data['category_id'],
                'title'             => $data['title'],
                'description'       => $data['description'] ?? null,
                'file_path'         => $filePath,
                'original_filename' => $file->getClientOriginalName(),
                'created_by'        => $authUserId,
                'updated_by'        => $authUserId,
            ]);

            return $portfolio->load(['category', 'studentAlumni']);
        });
    }

    public function deletePortfolio(StudentPortfolio $portfolio, ?int $authUserId = null): bool
    {
        return DB::transaction(function () use ($portfolio, $authUserId) {
            $portfolio->updated_by = $authUserId;
            $portfolio->deleted_by = $authUserId;
            $portfolio->save();

            if ($portfolio->file_path && Storage::disk('public')->exists($portfolio->file_path)) {
                // Kept or deleted based on policy, soft deleted
            }

            return (bool) $portfolio->delete();
        });
    }
}

