<?php

namespace App\Services;

use App\Models\StudentAlumni;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentAlumniService
{
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
}
