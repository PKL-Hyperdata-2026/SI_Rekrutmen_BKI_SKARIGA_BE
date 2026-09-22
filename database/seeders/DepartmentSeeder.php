<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'id' => 1,
                'code' => 'ELEKTRO',
                'name' => 'Teknik Elektro',
                'description' => 'Departemen bidang keahlian elektronika, audio video, elektronika industri, kimia industri, dan ketenagalistrikan.',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'code' => 'MESIN',
                'name' => 'Teknik Mesin',
                'description' => 'Departemen bidang keahlian bisnis digital & pemasaran, pengelasan, dan manufaktur pemesinan.',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'code' => 'OTOMOTIF',
                'name' => 'Teknik Otomotif & Bisnis',
                'description' => 'Departemen bidang keahlian otomotif sepeda motor, kendaraan ringan, dan body otomotif.',
                'is_active' => true,
            ],
            [
                'id' => 4,
                'code' => 'TIK',
                'name' => 'Teknologi Informasi dan Komunikasi',
                'description' => 'Departemen bidang keahlian animasi, desain komunikasi visual, perfilman & penyiaran, jaringan komputer, dan rekayasa perangkat lunak.',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['id' => $department['id']],
                $department
            );
        }
    }
}
