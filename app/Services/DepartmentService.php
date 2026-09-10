<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DepartmentService
{
    public function getDepartments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (! empty($filters['for_select'])) {
            return $this->selectOptions($filters, $perPage);
        }

        $query = Department::query()->withCount('majors');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'code', 'name', 'is_active', 'created_at', 'majors_count'];
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
        $query = Department::query()
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
            fn (Department $department): array => [
                'value' => $department->id,
                'label' => trim($department->name.' ('.$department->code.')'),
                'extra' => ['code' => $department->code],
            ]
        );
    }

    public function createDepartment(array $data, ?int $authUserId = null): Department
    {
        return DB::transaction(function () use ($data, $authUserId) {
            return Department::create([
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $authUserId,
                'updated_by' => $authUserId,
            ]);
        });
    }

    public function updateDepartment(Department $department, array $data, ?int $authUserId = null): Department
    {
        return DB::transaction(function () use ($department, $data, $authUserId) {
            $department->update([
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'description' => array_key_exists('description', $data) ? $data['description'] : $department->description,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $department->is_active,
                'updated_by' => $authUserId,
            ]);

            return $department->fresh()->loadCount('majors');
        });
    }

    public function deleteDepartment(Department $department, ?int $authUserId = null): bool
    {
        if ($department->majors()->exists()) {
            throw new InvalidArgumentException('Departemen tidak dapat dihapus karena masih memiliki jurusan terkait.');
        }

        return DB::transaction(function () use ($department, $authUserId) {
            $department->update([
                'deleted_by' => $authUserId,
            ]);

            return (bool) $department->delete();
        });
    }

    public function toggleActive(Department $department, ?int $authUserId = null): Department
    {
        return DB::transaction(function () use ($department, $authUserId) {
            $department->update([
                'is_active' => ! $department->is_active,
                'updated_by' => $authUserId,
            ]);

            return $department->fresh()->loadCount('majors');
        });
    }
}
