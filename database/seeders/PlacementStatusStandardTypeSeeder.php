<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class PlacementStatusStandardTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Category: placement_status
        $statusCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'placement_status'],
            ['name' => 'Status Penempatan Kerja', 'description' => 'Status berlangsungnya penempatan/bertahan kerja alumni']
        );

        $statuses = [
            ['code' => 'active', 'name' => 'Masih Bekerja / Aktif', 'metadata' => ['badge_color' => 'green', 'icon' => 'user-check'], 'sort_order' => 1],
            ['code' => 'resigned', 'name' => 'Resign / Kontrak Habis', 'metadata' => ['badge_color' => 'red', 'icon' => 'log-out'], 'sort_order' => 2],
            ['code' => 'moved', 'name' => 'Pindah Perusahaan Lain', 'metadata' => ['badge_color' => 'blue', 'icon' => 'building-2'], 'sort_order' => 3],
        ];

        $contractEnd = StandardType::where('category_id', $statusCategory->id)->where('code', 'contract_end')->first();
        if ($contractEnd) {
            $contractEnd->update([
                'code' => 'moved',
                'name' => 'Pindah Perusahaan Lain',
                'metadata' => ['badge_color' => 'blue', 'icon' => 'building-2'],
                'sort_order' => 3,
            ]);
        }

        foreach ($statuses as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $statusCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }
    }
}
