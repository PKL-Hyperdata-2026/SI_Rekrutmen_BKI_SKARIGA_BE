<?php

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class RecruitmentAttendanceStandardTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Category: attendance_status
        $statusCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'attendance_status'],
            ['name' => 'Status Kehadiran Rekrutmen', 'description' => 'Status presensi fisik/online peserta pada tahapan seleksi']
        );

        $statuses = [
            ['code' => 'present', 'name' => 'Hadir', 'metadata' => ['badge_color' => 'green', 'icon' => 'user-check'], 'sort_order' => 1],
            ['code' => 'leave', 'name' => 'Izin', 'metadata' => ['badge_color' => 'orange', 'icon' => 'user-minus'], 'sort_order' => 2],
            ['code' => 'Absent', 'name' => 'Alpa / Tidak Hadir', 'metadata' => ['badge_color' => 'red', 'icon' => 'user-x'], 'sort_order' => 3],
        ];

        foreach ($statuses as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $statusCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }
    }
}
