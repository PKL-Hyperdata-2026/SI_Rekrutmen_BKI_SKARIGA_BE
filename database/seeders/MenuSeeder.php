<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AccessMenu;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $adminMenus = [
            ['name' => 'Dashboard', 'link' => '/admin/dashboard', 'icon' => 'LayoutDashboard', 'section' => 1, 'superadmin_only' => false],
            ['name' => 'Data Departemen', 'link' => '/admin/departemen', 'icon' => 'Network', 'section' => 2, 'superadmin_only' => false],
            ['name' => 'Data Jurusan', 'link' => '/admin/jurusan', 'icon' => 'Shapes', 'section' => 2, 'superadmin_only' => false],
            ['name' => 'Manajemen Pengguna', 'link' => '/admin/users', 'icon' => 'UserCog', 'section' => 2, 'superadmin_only' => true],
            ['name' => 'Data Siswa', 'link' => '/admin/siswa', 'icon' => 'Users', 'section' => 2, 'superadmin_only' => false],
            ['name' => 'Data Alumni', 'link' => '/admin/alumni', 'icon' => 'GraduationCap', 'section' => 2, 'superadmin_only' => false],
            ['name' => 'Perusahaan DUDI', 'link' => '/admin/dudi', 'icon' => 'Building2', 'section' => 2, 'superadmin_only' => false],
            ['name' => 'Lowongan Kerja', 'link' => '/admin/lowongan', 'icon' => 'Briefcase', 'section' => 3, 'superadmin_only' => false],
            ['name' => 'Seleksi Rekrutmen', 'link' => '/admin/seleksi', 'icon' => 'CheckSquare', 'section' => 3, 'superadmin_only' => false],
            ['name' => 'Validasi Presensi', 'link' => '/admin/validasi-presensi', 'icon' => 'CalendarCheck', 'section' => 3, 'superadmin_only' => false],
            ['name' => 'Tracer Study', 'link' => '/admin/tracer', 'icon' => 'LineChart', 'section' => 3, 'superadmin_only' => false],
            ['name' => 'Laporan & Cetak', 'link' => '/admin/laporan', 'icon' => 'Printer', 'section' => 3, 'superadmin_only' => false],
            ['name' => 'Hak Akses', 'link' => '/admin/access-menus', 'icon' => 'ShieldCheck', 'section' => 4, 'superadmin_only' => true],
            ['name' => 'Template Pesan', 'link' => '/admin/message-templates', 'icon' => 'Mail', 'section' => 4, 'superadmin_only' => false],
        ];

        foreach ($adminMenus as $m) {
            $menu = Menu::updateOrCreate(
                ['menu_link' => $m['link']],
                [
                    'menu_name' => $m['name'],
                    'menu_icon' => $m['icon'],
                    'section' => $m['section'],
                    'is_active' => true,
                ]
            );

            AccessMenu::firstOrCreate(['role' => 'superadmin', 'menu_id' => $menu->id]);

            if (! $m['superadmin_only']) {
                AccessMenu::firstOrCreate(['role' => 'admin', 'menu_id' => $menu->id]);
            }
        }
    }
}
