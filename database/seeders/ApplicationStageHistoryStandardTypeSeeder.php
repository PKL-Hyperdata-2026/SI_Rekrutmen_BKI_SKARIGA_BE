<?php

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class ApplicationStageHistoryStandardTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Category: application_stage_status
        $statusCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'application_stage_status'],
            ['name' => 'Status Tahapan Seleksi', 'description' => 'Hasil status pelamar pada tiap tahapan seleksi']
        );

        $statuses = [
            ['code' => 'scheduled', 'name' => 'Dijadwalkan (Scheduled)', 'metadata' => ['badge_color' => 'blue', 'icon' => 'calendar'], 'sort_order' => 1],
            ['code' => 'passed', 'name' => 'Lolos (Passed)', 'metadata' => ['badge_color' => 'green', 'icon' => 'check-circle'], 'sort_order' => 2],
            ['code' => 'failed', 'name' => 'Gagal (Failed)', 'metadata' => ['badge_color' => 'red', 'icon' => 'x-circle'], 'sort_order' => 3],
            ['code' => 'absent', 'name' => 'Tidak Hadir (Absent)', 'metadata' => ['badge_color' => 'orange', 'icon' => 'user-x'], 'sort_order' => 4],
        ];

        foreach ($statuses as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $statusCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }
    }
}
