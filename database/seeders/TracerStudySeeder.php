<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Major;
use App\Models\StudentAlumni;
use App\Models\TracerStudy;
use App\Models\User;
use Illuminate\Database\Seeder;

class TracerStudySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create([
            'full_name' => 'Admin BKI',
            'email'     => 'admin@skariga.sch.id',
            'role'      => 'admin',
        ]);

        $rpl = Major::firstOrCreate(['code' => 'RPL'], ['name' => 'Rekayasa Perangkat Lunak', 'is_active' => true]);
        $dkv = Major::firstOrCreate(['code' => 'DKV'], ['name' => 'Desain Komunikasi Visual', 'is_active' => true]);
        $el  = Major::firstOrCreate(['code' => 'TE'], ['name' => 'Teknik Elektro', 'is_active' => true]);
        $tm  = Major::firstOrCreate(['code' => 'TM'], ['name' => 'Teknik Mesin', 'is_active' => true]);

        $mockData = [
            [
                'name'            => 'Windah Barusadar',
                'nis'             => '250491',
                'major_id'        => $rpl->id,
                'graduation_year' => 2026,
                'career_status'   => 'bekerja',
                'company_name'    => 'PT Hyperdata Indo',
                'company_sector'  => 'Sektor : Teknologi Komputer',
                'job_title'       => 'UI UX Design',
                'job_location'    => 'Malang, Jawa Timur',
                'accepted_date'   => '2026-01-10',
                'start_date'      => '2026-02-19',
                'waiting_period'  => '1 Bulan',
                'minimum_salary'  => 4800000,
                'maximum_salary'  => 4800000,
            ],
            [
                'name'            => 'Jesllyn Hartono',
                'nis'             => '219023',
                'major_id'        => $dkv->id,
                'graduation_year' => 2026,
                'career_status'   => 'bekerja',
                'company_name'    => 'PT Humaira Tech',
                'company_sector'  => 'Sektor : Teknologi Digital',
                'job_title'       => 'Desainer Grafis',
                'job_location'    => 'Karawang, Jawa Barat',
                'accepted_date'   => '2025-12-28',
                'start_date'      => '2026-01-01',
                'waiting_period'  => '2 Bulan',
                'minimum_salary'  => 5500000,
                'maximum_salary'  => 5500000,
            ],
            [
                'name'            => 'Bambang Sugeh',
                'nis'             => '221002',
                'major_id'        => $el->id,
                'graduation_year' => 2025,
                'career_status'   => 'lanjut_studi',
                'university_name' => 'Universitas Brawijaya',
                'company_sector'  => 'Perguruan Tinggi Swasta',
                'study_program'   => 'D4 Teknik Elektro',
                'job_location'    => 'Malang, Jatim',
                'waiting_period'  => '0 Bulan',
            ],
            [
                'name'            => 'Tasya Craveli',
                'nis'             => '252890',
                'major_id'        => $tm->id,
                'graduation_year' => 2025,
                'career_status'   => 'bekerja',
                'company_name'    => 'PT Kompas Jatim',
                'company_sector'  => 'Sektor : Teknologi Digital',
                'job_title'       => 'Shoot Film',
                'job_location'    => 'Kubu Raya, Kaltim',
                'accepted_date'   => '2025-11-09',
                'start_date'      => '2025-11-21',
                'waiting_period'  => '1 Bulan',
                'minimum_salary'  => 2300000,
                'maximum_salary'  => 2300000,
            ],
            [
                'name'            => 'Marvello Cikiwaw',
                'nis'             => '21111110',
                'major_id'        => $tm->id,
                'graduation_year' => 2025,
                'career_status'   => 'bekerja',
                'company_name'    => 'PT Astra Honda Motor',
                'company_sector'  => 'Sektor : Otomotif Manufaktur',
                'job_title'       => 'Junior Mechanic',
                'job_location'    => 'Karawang, Jabar',
                'accepted_date'   => '2026-01-15',
                'start_date'      => '2026-02-01',
                'waiting_period'  => '1 Bulan',
                'minimum_salary'  => 4800000,
                'maximum_salary'  => 4800000,
            ],
            [
                'name'                => 'Rian Hidayat',
                'nis'                 => '253100',
                'major_id'            => $rpl->id,
                'graduation_year'     => 2025,
                'career_status'       => 'wirausaha',
                'business_name'       => 'Kedai Kopi Skariga',
                'business_field'      => 'Kuliner',
                'business_address'    => 'Jl. Danau Ranau No. 12, Sawojajar, Malang',
                'job_location'        => 'Malang, Jawa Timur',
                'instagram_handle'    => '@kedaikopis kariga',
                'average_income'      => '5.000.000 - 10.000.000',
                'business_start_date' => '2025-08-01',
            ],
            [
                'name'            => 'Dika Pratama',
                'nis'             => '254200',
                'major_id'        => $rpl->id,
                'graduation_year' => 2026,
                'career_status'   => 'mencari_pekerjaan',
            ],
        ];

        foreach ($mockData as $item) {
            $user = User::firstOrCreate(
                ['email' => strtolower(str_replace(' ', '', $item['name'])) . '@alumni.skariga.sch.id'],
                [
                    'full_name' => $item['name'],
                    'role'      => 'alumni',
                    'is_active' => true,
                    'password'  => bcrypt('password'),
                ]
            );

            $alumni = StudentAlumni::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nis'             => $item['nis'],
                    'major_id'        => $item['major_id'],
                    'graduation_year' => $item['graduation_year'],
                    'is_active'       => true,
                ]
            );

            TracerStudy::updateOrCreate(
                ['student_alumni_id' => $alumni->id],
                [
                    'career_status'       => $item['career_status'],
                    'company_name'        => $item['company_name'] ?? null,
                    'company_sector'      => $item['company_sector'] ?? null,
                    'job_title'           => $item['job_title'] ?? null,
                    'job_location'        => $item['job_location'] ?? null,
                    'minimum_salary'      => $item['minimum_salary'] ?? null,
                    'maximum_salary'      => $item['maximum_salary'] ?? null,
                    'waiting_period'      => $item['waiting_period'] ?? null,
                    'accepted_date'       => $item['accepted_date'] ?? null,
                    'start_date'          => $item['start_date'] ?? null,
                    'university_name'     => $item['university_name'] ?? null,
                    'study_program'       => $item['study_program'] ?? null,
                    'business_name'       => $item['business_name'] ?? null,
                    'business_address'    => $item['business_address'] ?? null,
                    'business_field'      => $item['business_field'] ?? null,
                    'instagram_handle'    => $item['instagram_handle'] ?? null,
                    'average_income'      => $item['average_income'] ?? null,
                    'business_start_date' => $item['business_start_date'] ?? null,
                    'created_by'          => $admin->id,
                    'updated_by'          => $admin->id,
                ]
            );
        }
    }
}
