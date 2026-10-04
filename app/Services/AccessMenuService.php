<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccessMenu;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccessMenuService
{
    public const ROLES = ['superadmin', 'admin', 'hrd', 'siswa', 'alumni'];

    public function getMatrix(): array
    {
        $menus = Menu::with('accessMenus')
            ->orderBy('section')
            ->orderBy('id')
            ->get()
            ->map(function ($menu) {
                $grantedRoles = $menu->accessMenus->pluck('role')->all();

                return [
                    'id' => $menu->id,
                    'name' => $menu->menu_name,
                    'icon' => $menu->menu_icon,
                    'link' => $menu->menu_link,
                    'section' => $menu->section,
                    'is_active' => $menu->is_active,
                    'granted_roles' => $grantedRoles,
                ];
            });

        return [
            'roles' => self::ROLES,
            'matrix' => $menus,
        ];
    }

    public function updateRolePermissions(string $role, array $menuIds, int $adminUserId): void
    {
        DB::transaction(function () use ($role, $menuIds, $adminUserId): void {
            AccessMenu::where('role', $role)->forceDelete();

            foreach ($menuIds as $menuId) {
                AccessMenu::create([
                    'role' => $role,
                    'menu_id' => (int) $menuId,
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
            }
        });
    }

    public function getUserMenus(User $user): Collection
    {
        return Menu::whereHas('accessMenus', fn ($q) => $q->where('role', $user->role))
            ->where('is_active', true)
            ->orderBy('section')
            ->orderBy('id')
            ->get();
    }
}
