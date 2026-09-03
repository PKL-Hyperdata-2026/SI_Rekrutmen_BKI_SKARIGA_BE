<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = StandardTypeCategory::firstOrCreate(
            ['code' => 'class'],
            [
                'name' => 'Kelas',
                'description' => 'Kelas siswa aktif (contoh: XII RPL 1)',
            ]
        );

        $classes = [
            ['code' => 'x_rpl_1', 'name' => 'X RPL 1', 'sort_order' => 1],
            ['code' => 'xi_rpl_1', 'name' => 'XI RPL 1', 'sort_order' => 2],
            ['code' => 'xii_rpl_1', 'name' => 'XII RPL 1', 'sort_order' => 3],
            ['code' => 'xii_rpl_2', 'name' => 'XII RPL 2', 'sort_order' => 4],
            ['code' => 'xii_tkj_1', 'name' => 'XII TKJ 1', 'sort_order' => 5],
            ['code' => 'xii_dkv_1', 'name' => 'XII DKV 1', 'sort_order' => 6],
        ];

        foreach ($classes as $class) {
            StandardType::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'code' => $class['code'],
                ],
                [
                    'name' => $class['name'],
                    'sort_order' => $class['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
