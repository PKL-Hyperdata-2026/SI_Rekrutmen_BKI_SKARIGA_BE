<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class TracerStudyStandardTypeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Category: employment_status
        $employmentCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'employment_status'],
            ['name' => 'Status Kebekerjaan', 'description' => 'Status aktivitas/bekerja alumni']
        );

        $employmentStatuses = [
            ['code' => 'working', 'name' => 'Bekerja', 'metadata' => ['badge_color' => 'blue'], 'sort_order' => 1],
            ['code' => 'studying', 'name' => 'Kuliah / Studi Lanjut', 'metadata' => ['badge_color' => 'purple'], 'sort_order' => 2],
            ['code' => 'entrepreneur', 'name' => 'Wirausaha', 'metadata' => ['badge_color' => 'emerald'], 'sort_order' => 3],
            ['code' => 'job_seeking', 'name' => 'Mencari Kerja', 'metadata' => ['badge_color' => 'orange'], 'sort_order' => 4],
        ];

        foreach ($employmentStatuses as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $employmentCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }

        // 2. Category: relevance_status
        $relevanceCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'relevance_status'],
            ['name' => 'Kesesuaian Jurusan', 'description' => 'Tingkat kesesuaian pekerjaan/studi dengan jurusan di sekolah']
        );

        $relevanceStatuses = [
            ['code' => 'relevant', 'name' => 'Sesuai', 'metadata' => ['badge_color' => 'green'], 'sort_order' => 1],
            ['code' => 'not_relevant', 'name' => 'Tidak Sesuai', 'metadata' => ['badge_color' => 'red'], 'sort_order' => 2],
        ];

        foreach ($relevanceStatuses as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $relevanceCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }

        // 3. Category: income_range
        $incomeCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'income_range'],
            ['name' => 'Range Pendapatan', 'description' => 'Kisaran pendapatan per bulan alumni']
        );

        $incomeRanges = [
            ['code' => 'under_1m', 'name' => '< Rp 1.000.000', 'metadata' => ['badge_color' => 'gray'], 'sort_order' => 1],
            ['code' => '1m_3m', 'name' => 'Rp 1.000.000 - Rp 3.000.000', 'metadata' => ['badge_color' => 'cyan'], 'sort_order' => 2],
            ['code' => '3m_5m', 'name' => 'Rp 3.000.000 - Rp 5.000.000', 'metadata' => ['badge_color' => 'blue'], 'sort_order' => 3],
            ['code' => 'above_5m', 'name' => '> Rp 5.000.000', 'metadata' => ['badge_color' => 'indigo'], 'sort_order' => 4],
        ];

        foreach ($incomeRanges as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $incomeCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }
    }
}