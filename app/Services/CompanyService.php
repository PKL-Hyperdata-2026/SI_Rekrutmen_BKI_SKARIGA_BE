<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\StandardType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CompanyService
{
    /** @var array<int, string> */
    protected array $sortableColumns = [
        'id',
        'name',
        'email',
        'phone',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Company::with(['industry', 'createdBy', 'updatedBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('pic_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('industry', function (Builder $industryQuery) use ($search) {
                        $industryQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['industry_id'])) {
            $query->where('industry_id', $filters['industry_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortByRaw = $filters['sort_by'] ?? 'id';
        $sortBy = in_array($sortByRaw, $this->sortableColumns, true) ? $sortByRaw : 'id';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function show(Company $company): Company
    {
        return $company->load(['industry', 'createdBy', 'updatedBy']);
    }

    public function create(array $data, ?int $actorId = null): Company
    {
        $data['created_by'] = $actorId;
        $data['updated_by'] = $actorId;

        $company = Company::create($data);

        return $company->load(['industry', 'createdBy', 'updatedBy']);
    }

    public function update(Company $company, array $data, ?int $actorId = null): Company
    {
        $data['updated_by'] = $actorId;

        $company->update($data);

        return $company->fresh(['industry', 'createdBy', 'updatedBy']);
    }

    public function delete(Company $company, ?int $actorId = null): bool
    {
        $company->updated_by = $actorId;
        $company->deleted_by = $actorId;
        $company->save();

        return $company->delete();
    }

    public function toggleActive(Company $company, ?int $actorId = null): Company
    {
        $company->is_active = ! $company->is_active;
        $company->updated_by = $actorId;
        $company->save();

        return $company->fresh(['industry', 'createdBy', 'updatedBy']);
    }

    public function getFormOptions(): array
    {
        $industries = StandardType::byCategory('company_industry')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'metadata']);

        return [
            'industries' => $industries,
        ];
    }
}
