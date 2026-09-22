<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Major;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MajorService
{
    public function getMajors(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (! empty($filters['for_select'])) {
            return $this->selectOptions($filters, $perPage);
        }

        $query = Major::query()->with(['department' => function ($q) {
            $q->select(['id', 'code', 'name']);
        }]);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            if ($search !== '') {
                $escaped = addcslashes($search, '%_\\');
                $query->where(function (Builder $q) use ($escaped) {
                    $q->where('name', 'like', "%{$escaped}%")
                        ->orWhere('code', 'like', "%{$escaped}%")
                        ->orWhere('description', 'like', "%{$escaped}%")
                        ->orWhereHas('department', function (Builder $deptQuery) use ($escaped) {
                            $deptQuery->where('name', 'like', "%{$escaped}%")
                                ->orWhere('code', 'like', "%{$escaped}%");
                        });
                });
            }
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'code', 'name', 'department_id', 'is_active', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function getFormOptions(): array
    {
        return [
            'departments' => Department::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ];
    }

    /**
     * Paginated lightweight options for async selects.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array{value: mixed, label: string, extra: array<string, mixed>}>
     */
    protected function selectOptions(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Major::query()
            ->where('is_active', true)
            ->select('id', 'code', 'name');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage)->through(
            fn (Major $major): array => [
                'value' => $major->id,
                'label' => $major->name,
                'extra' => ['code' => $major->code],
            ]
        );
    }

    public function show(Major $major): Major
    {
        return $major->load('department');
    }

    public function createMajor(array $data, ?int $authUserId = null): Major
    {
        return DB::transaction(function () use ($data, $authUserId) {
            $major = Major::create([
                'department_id' => (int) $data['department_id'],
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $authUserId,
                'updated_by' => $authUserId,
            ]);

            return $major->load('department');
        });
    }

    public function updateMajor(Major $major, array $data, ?int $authUserId = null): Major
    {
        return DB::transaction(function () use ($major, $data, $authUserId) {
            $major->update([
                'department_id' => array_key_exists('department_id', $data) ? (int) $data['department_id'] : $major->department_id,
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'description' => array_key_exists('description', $data) ? $data['description'] : $major->description,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $major->is_active,
                'updated_by' => $authUserId,
            ]);

            return $major->fresh()->load('department');
        });
    }

    public function deleteMajor(Major $major, ?int $authUserId = null): bool
    {
        if ($major->studentsAlumni()->exists()) {
            throw new InvalidArgumentException('Jurusan tidak dapat dihapus karena masih digunakan oleh data siswa/alumni.');
        }

        if ($major->jobVacancies()->exists()) {
            throw new InvalidArgumentException('Jurusan tidak dapat dihapus karena masih terkait dengan lowongan kerja.');
        }

        return DB::transaction(function () use ($major, $authUserId) {
            $major->update([
                'deleted_by' => $authUserId,
            ]);

            return (bool) $major->delete();
        });
    }

    public function toggleActive(Major $major, ?int $authUserId = null): Major
    {
        return DB::transaction(function () use ($major, $authUserId) {
            $major->update([
                'is_active' => ! $major->is_active,
                'updated_by' => $authUserId,
            ]);

            return $major->fresh()->load('department');
        });
    }
}
