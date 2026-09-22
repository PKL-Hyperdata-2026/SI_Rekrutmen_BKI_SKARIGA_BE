<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Major;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    public function run(): void
    {
        if (Department::count() === 0) {
            $this->call(DepartmentSeeder::class);
        }

        $majors = [
            [
                'code' => 'PB',
                'name' => 'Teknik Pembangkit Tenaga Listrik',
                'description' => 'Program Keahlian Pembangkitan, Distribusi, dan Instalasi Tenaga Listrik',
                'department_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'TEAV',
                'name' => 'Teknik Elektronika Audio Video',
                'description' => 'Program Keahlian Peralatan Audio, Video, dan Sistem Elektronika',
                'department_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'EI',
                'name' => 'Elektronika Industri',
                'description' => 'Program Keahlian Otomasi dan Instrumentasi Elektronika Industri',
                'department_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'KL',
                'name' => 'Teknik Kimia Industri',
                'description' => 'Program Keahlian Pemrosesan Kimia Industri dan Analisis Laboratorium',
                'department_id' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'TP',
                'name' => 'Teknik Pemesinan',
                'description' => 'Program Keahlian Teknik Mesin Manufaktur dan CNC',
                'department_id' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'TL',
                'name' => 'Teknik Pengelasan',
                'description' => 'Program Keahlian Teknik Pengelasan dan Fabrikasi Logam',
                'department_id' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'BDP',
                'name' => 'Bisnis Daring dan Pemasaran',
                'description' => 'Program Keahlian Bisnis Daring, E-Commerce, dan Pemasaran Digital',
                'department_id' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'TKR',
                'name' => 'Teknik Kendaraan Ringan',
                'description' => 'Program Keahlian Teknik Otomotif Kendaraan Ringan',
                'department_id' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'TSM',
                'name' => 'Teknik Sepeda Motor',
                'description' => 'Program Keahlian Teknik Otomotif Sepeda Motor',
                'department_id' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'BO',
                'name' => 'Teknik Body Otomotif',
                'description' => 'Program Keahlian Perbaikan Bodi dan Pengecatan Kendaraan',
                'department_id' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'NIMA',
                'name' => 'Animasi',
                'description' => 'Program Keahlian Seni Animasi dan Multimedia Digital',
                'department_id' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'DKV',
                'name' => 'Desain Komunikasi Visual',
                'description' => 'Program Keahlian Seni Desain Grafis dan Visual Komunikasi',
                'department_id' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'BP',
                'name' => 'Broadcasting dan Perfilman',
                'description' => 'Program Keahlian Produksi Siaran dan Program Radio/Televisi/Film',
                'department_id' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'RPL',
                'name' => 'Rekayasa Perangkat Lunak',
                'description' => 'Program Keahlian Pengembangan Perangkat Lunak dan Gim',
                'department_id' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'TKJ',
                'name' => 'Teknik Komputer dan Jaringan',
                'description' => 'Program Keahlian Teknik Komputer dan Telekomunikasi',
                'department_id' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($majors as $major) {
            Major::updateOrCreate(
                ['code' => $major['code']],
                $major
            );
        }
    }
}
