<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function getUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()->with('company');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        } else {
            $query->where('role', '!=', 'superadmin');
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['full_name', 'email', 'role', 'is_active', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function getFormOptions(): array
    {
        $companies = Company::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'user_id']);

        return [
            'roles' => [
                ['value' => 'admin', 'label' => 'Admin BKI'],
                ['value' => 'hrd', 'label' => 'HRD Perusahaan'],
            ],
            'companies' => $companies,
        ];
    }

    public function createUser(array $data, ?int $authUserId = null): User
    {
        if ($data['role'] === 'superadmin') {
            $superadminExists = User::where('role', 'superadmin')->exists();
            if ($superadminExists) {
                throw new \InvalidArgumentException('Hanya boleh terdapat satu akun Superadmin dalam sistem.');
            }
        }

        return DB::transaction(function () use ($data, $authUserId) {
            $userData = [
                'full_name'  => $data['full_name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'] ?? null,
                'password'   => $data['password'],
                'role'       => $data['role'],
                'is_active'  => $data['is_active'] ?? true,
                'created_by' => $authUserId,
                'updated_by' => $authUserId,
            ];

            $user = User::create($userData);

            if ($data['role'] === 'hrd' && ! empty($data['company_id'])) {
                Company::where('id', $data['company_id'])->update([
                    'user_id'    => $user->id,
                    'updated_by' => $authUserId,
                ]);
            }

            return $user->load('company');
        });
    }

    public function show(User $user): User
    {
        return $user->load('company');
    }

    public function updateUser(User $user, array $data, ?int $authUserId = null): User
    {
        if (isset($data['role']) && $data['role'] === 'superadmin' && $user->role !== 'superadmin') {
            $superadminExists = User::where('role', 'superadmin')->where('id', '!=', $user->id)->exists();
            if ($superadminExists) {
                throw new \InvalidArgumentException('Hanya boleh terdapat satu akun Superadmin dalam sistem.');
            }
        }

        return DB::transaction(function () use ($user, $data, $authUserId) {
            $userUpdates = [
                'updated_by' => $authUserId,
            ];

            if (isset($data['full_name'])) {
                $userUpdates['full_name'] = $data['full_name'];
            }
            if (isset($data['email'])) {
                $userUpdates['email'] = $data['email'];
            }
            if (array_key_exists('phone', $data)) {
                $userUpdates['phone'] = $data['phone'];
            }
            if (! empty($data['password'])) {
                $userUpdates['password'] = $data['password'];
            }
            if (isset($data['role'])) {
                $userUpdates['role'] = $data['role'];
            }
            if (isset($data['is_active'])) {
                $userUpdates['is_active'] = $data['is_active'];
            }

            $user->update($userUpdates);

            if (isset($data['role']) && $data['role'] === 'hrd' && array_key_exists('company_id', $data)) {
                Company::where('user_id', $user->id)->update([
                    'user_id'    => null,
                    'updated_by' => $authUserId,
                ]);

                if (! empty($data['company_id'])) {
                    Company::where('id', $data['company_id'])->update([
                        'user_id'    => $user->id,
                        'updated_by' => $authUserId,
                    ]);
                }
            } elseif (isset($data['role']) && $data['role'] !== 'hrd') {
                Company::where('user_id', $user->id)->update([
                    'user_id'    => null,
                    'updated_by' => $authUserId,
                ]);
            }

            return $user->fresh(['company']);
        });
    }

    public function toggleActive(User $user, ?int $authUserId = null): User
    {
        $user->update([
            'is_active'  => ! $user->is_active,
            'updated_by' => $authUserId,
        ]);

        return $user->load('company');
    }

    public function resetPassword(User $user, string $newPassword, ?int $authUserId = null): User
    {
        $user->update([
            'password'   => $newPassword,
            'updated_by' => $authUserId,
        ]);

        return $user;
    }

    public function deleteUser(User $user, ?int $authUserId = null): bool
    {
        return DB::transaction(function () use ($user, $authUserId) {
            Company::where('user_id', $user->id)->update([
                'user_id'    => null,
                'updated_by' => $authUserId,
            ]);

            $user->update(['deleted_by' => $authUserId]);
            return (bool) $user->delete();
        });
    }
}
