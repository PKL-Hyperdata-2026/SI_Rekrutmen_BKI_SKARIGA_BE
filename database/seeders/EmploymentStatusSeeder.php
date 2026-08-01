<?php

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class EmploymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = StandardTypeCategory::firstOrCreate(
            ['code' => 'employment_status'],
            [
                'name' => 'Status Kerja',
                'description' => 'Status kerja siswa/alumni untuk tracer study',
            ]
        );

        $statuses = [
            ['code' => 'mencari_kerja', 'name' => 'Mencari Kerja', 'sort_order' => 1],
            ['code' => 'bekerja', 'name' => 'Bekerja', 'sort_order' => 2],
            ['code' => 'kuliah', 'name' => 'Melanjutkan Studi/Kuliah', 'sort_order' => 3],
            ['code' => 'wirausaha', 'name' => 'Wirausaha', 'sort_order' => 4],
        ];

        foreach ($statuses as $status) {
            StandardType::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'code' => $status['code'],
                ],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
