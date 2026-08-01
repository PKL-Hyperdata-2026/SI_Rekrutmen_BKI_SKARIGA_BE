<?php

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class JobVacancyStandardTypeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Category: job_type
        $jobTypeCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'job_type'],
            ['name' => 'Tipe Pekerjaan', 'description' => 'Kategori tipe kontrak/hubungan kerja lowongan']
        );

        $jobTypes = [
            ['code' => 'full_time', 'name' => 'Full-time / Penuh Waktu', 'metadata' => ['badge_color' => 'blue'], 'sort_order' => 1],
            ['code' => 'contract', 'name' => 'Kontrak', 'metadata' => ['badge_color' => 'purple'], 'sort_order' => 2],
            ['code' => 'internship', 'name' => 'Magang / PKL', 'metadata' => ['badge_color' => 'teal'], 'sort_order' => 3],
            ['code' => 'part_time', 'name' => 'Part-time / Paruh Waktu', 'metadata' => ['badge_color' => 'orange'], 'sort_order' => 4],
        ];

        foreach ($jobTypes as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $jobTypeCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }

        // 2. Category: vacancy_status
        $statusCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'vacancy_status'],
            ['name' => 'Status Lowongan', 'description' => 'Status publikasi dan alur lowongan kerja']
        );

        $statuses = [
            ['code' => 'draft', 'name' => 'Draft', 'metadata' => ['badge_color' => 'gray', 'icon' => 'file-edit'], 'sort_order' => 1],
            ['code' => 'published', 'name' => 'Diterbitkan', 'metadata' => ['badge_color' => 'green', 'icon' => 'check-circle'], 'sort_order' => 2],
            ['code' => 'closed', 'name' => 'Ditutup', 'metadata' => ['badge_color' => 'red', 'icon' => 'x-circle'], 'sort_order' => 3],
            ['code' => 'archived', 'name' => 'Diarsipkan', 'metadata' => ['badge_color' => 'amber', 'icon' => 'archive'], 'sort_order' => 4],
        ];

        foreach ($statuses as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $statusCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }

        // 3. Category: target_applicant
        $targetCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'target_applicant'],
            ['name' => 'Target Pelamar', 'description' => 'Sasaran kualifikasi pelamar (Siswa/Alumni)']
        );

        $targets = [
            ['code' => 'all', 'name' => 'Semua (Siswa & Alumni)', 'metadata' => ['badge_color' => 'indigo'], 'sort_order' => 1],
            ['code' => 'class_12_only', 'name' => 'Khusus Siswa Kelas 12', 'metadata' => ['badge_color' => 'cyan'], 'sort_order' => 2],
            ['code' => 'alumni_only', 'name' => 'Khusus Alumni', 'metadata' => ['badge_color' => 'emerald'], 'sort_order' => 3],
        ];

        foreach ($targets as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $targetCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }
    }
}
