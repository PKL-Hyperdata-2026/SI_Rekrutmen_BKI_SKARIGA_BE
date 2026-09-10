<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Major;
use App\Models\StandardType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StandardTypeService
{
    /**
     * Paginated lightweight options for async selects.
     *
     * @return LengthAwarePaginator<int, array{value: mixed, label: string, extra: array<string, mixed>}>
     */
    public function getSelectOptions(string $category, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = StandardType::byCategory($category)
            ->where('is_active', true)
            ->select('id', 'code', 'name');

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $majors = $category === 'class' ? $this->activeMajors() : new Collection;

        return $query->orderBy('sort_order')->paginate($perPage)->through(
            fn (StandardType $type): array => [
                'value' => $type->id,
                'label' => $type->name,
                'extra' => [
                    'code' => $type->code,
                    'resolvedMajorId' => $category === 'class'
                        ? $this->resolveMajorIdForClass($type, $majors)
                        : null,
                    'resolvedMajorName' => $category === 'class'
                        ? $this->resolveMajorNameForClass($type, $majors)
                        : null,
                ],
            ]
        );
    }

    /**
     * @return Collection<int, Major>
     */
    protected function activeMajors(): Collection
    {
        return Major::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Mirror of the frontend resolveMajorByClass helper: match a class
     * to a major by code, then by name, inside the class display text.
     *
     * @param  Collection<int, Major>  $majors
     */
    protected function resolveMajorIdForClass(StandardType $class, Collection $majors): ?int
    {
        return $this->resolveMajorForClass($class, $majors)?->id;
    }

    /**
     * @param  Collection<int, Major>  $majors
     */
    protected function resolveMajorNameForClass(StandardType $class, Collection $majors): ?string
    {
        return $this->resolveMajorForClass($class, $majors)?->name;
    }

    /**
     * @param  Collection<int, Major>  $majors
     */
    protected function resolveMajorForClass(StandardType $class, Collection $majors): ?Major
    {
        $text = strtoupper(trim($class->name.' '.($class->code ?? '')));

        if ($text === '') {
            return null;
        }

        foreach ($majors as $major) {
            if ($major->code !== null && $major->code !== '' && str_contains($text, strtoupper($major->code))) {
                return $major;
            }
        }

        foreach ($majors as $major) {
            if (str_contains($text, strtoupper($major->name))) {
                return $major;
            }
        }

        return null;
    }
}
