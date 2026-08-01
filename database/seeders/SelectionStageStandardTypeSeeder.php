<?php

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class SelectionStageStandardTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Category: stage_type
        $stageTypeCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'stage_type'],
            ['name' => 'Tipe Tahapan Seleksi', 'description' => 'Kategori jenis atau tipe tahapan evaluasi rekrutmen']
        );

        $stageTypes = [
            ['code' => 'administration', 'name' => 'Seleksi Administrasi', 'metadata' => ['badge_color' => 'gray', 'icon' => 'file-text'], 'sort_order' => 1],
            ['code' => 'written_test', 'name' => 'Tes Tulis / Potensi Akademik', 'metadata' => ['badge_color' => 'blue', 'icon' => 'edit'], 'sort_order' => 2],
            ['code' => 'psychological_test', 'name' => 'Psikotes', 'metadata' => ['badge_color' => 'purple', 'icon' => 'brain'], 'sort_order' => 3],
            ['code' => 'coding_test', 'name' => 'Tes Ketrampilan', 'metadata' => ['badge_color' => 'code', 'icon' => 'terminal'], 'sort_order' => 4],
            ['code' => 'interview_hrd', 'name' => 'Wawancara HRD', 'metadata' => ['badge_color' => 'orange', 'icon' => 'users'], 'sort_order' => 5],
            ['code' => 'interview_user', 'name' => 'Wawancara User / Teknis', 'metadata' => ['badge_color' => 'amber', 'icon' => 'user-check'], 'sort_order' => 6],
            ['code' => 'medical_check_up', 'name' => 'Medical Check-Up (MCU)', 'metadata' => ['badge_color' => 'red', 'icon' => 'heart'], 'sort_order' => 7],
        ];

        foreach ($stageTypes as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $stageTypeCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }
    }
}
