<?php

namespace Database\Seeders;

use App\Models\Major;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $majors = [
            [
                'code' => 'RPL',
                'name' => 'Rekayasa Perangkat Lunak',
                'description' => 'Program Keahlian Pengembangan Perangkat Lunak dan Gim',
                'is_active' => true,
            ],
            [
                'code' => 'TKJ',
                'name' => 'Teknik Komputer dan Jaringan',
                'description' => 'Program Keahlian Teknik Komputer dan Telekomunikasi',
                'is_active' => true,
            ],
            [
                'code' => 'NIM',
                'name' => 'Animasi',
                'description' => 'Program Keahlian Seni Animasi dan Multimedia',
                'is_active' => true,
            ],
            [
                'code' => 'DKV',
                'name' => 'Desain Komunikasi Visual',
                'description' => 'Program Keahlian Seni Desain Grafis dan Visual Komunikasi',
                'is_active' => true,
            ],
            [
                'code' => 'BP',
                'name' => 'Broadcasting dan Perfilman',
                'description' => 'Program Keahlian Produksi Siaran dan Program Radio/Televisi/Film',
                'is_active' => true,
            ],
            [
                'code' => 'TP',
                'name' => 'Teknik Pemesinan',
                'description' => 'Program Keahlian Teknik Mesin Manufaktur dan CNC',
                'is_active' => true,
            ],
            [
                'code' => 'TL',
                'name' => 'Teknik Pengelasan',
                'description' => 'Program Keahlian Teknik Pengelasan dan Fabrikasi Logam',
                'is_active' => true,
            ],
            [
                'code' => 'BDP',
                'name' => 'Bisnis Digital & Pemasaran',
                'description' => 'Program Keahlian Bisnis Daring, E-Commerce, dan Pemasaran Digital',
                'is_active' => true,
            ],
            [
                'code' => 'TKR',
                'name' => 'Teknik Kendaraan Ringan',
                'description' => 'Program Keahlian Teknik Otomotif Mobil dan Kendaraan Ringan',
                'is_active' => true,
            ],
            [
                'code' => 'BO',
                'name' => 'Teknik Body Otomotif',
                'description' => 'Program Keahlian Perbaikan Bodi dan Pengecatan Kendaraan',
                'is_active' => true,
            ],
            [
                'code' => 'TSM',
                'name' => 'Teknik Sepeda Motor',
                'description' => 'Program Keahlian Teknik Otomotif Sepeda Motor',
                'is_active' => true,
            ],
            [
                'code' => 'TEI',
                'name' => 'Teknik Elektronika Industri',
                'description' => 'Program Keahlian Otomasi dan Elektronika Industri',
                'is_active' => true,
            ],
            [
                'code' => 'AV',
                'name' => 'Teknik Audio Video',
                'description' => 'Program Keahlian Peralatan Audio, Video, dan Elektronika Konsumen',
                'is_active' => true,
            ],
            [
                'code' => 'KL',
                'name' => 'Teknik Kimia Industri',
                'description' => 'Program Keahlian Pemrosesan Kimia Industri dan Analisis Laboratorium',
                'is_active' => true,
            ],
            [
                'code' => 'PB',
                'name' => 'Teknik Pembangkit Tenaga Listrik',
                'description' => 'Program Keahlian Pembangkitan, Distribusi, dan Instalasi Tenaga Listrik',
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
