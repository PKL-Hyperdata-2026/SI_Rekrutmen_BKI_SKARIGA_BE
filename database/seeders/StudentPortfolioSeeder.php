<?php

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class StudentPortfolioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Kategori Induk untuk Portofolio di standard_type_categories
        $portfolioTypeCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'portfolio_type'],
            ['name' => 'Jenis Portfolio', 'description' => 'Kategori dokumen pendukung E-Portfolio']
        );

        // 2. Buat Data Portofolio di standard_types
        $portfolioTypes = [
            ['code' => 'cv', 'name' => 'Curriculum Vitae (CV)', 'sort_order' => 1],
            ['code' => 'sertifikat_pkl', 'name' => 'Sertifikat PKL/Magang', 'sort_order' => 2],
            ['code' => 'sertifikat_prestasi', 'name' => 'Sertifikasi Prestasi', 'sort_order' => 3],
            ['code' => 'sertifikat_bahasa', 'name' => 'Sertifikat Bahasa (TOEIC/JLPT)', 'sort_order' => 4],
        ];

        // 3. Insert / Update ke Database
        foreach ($portfolioTypes as $type) {
            StandardType::updateOrCreate(
                [
                    'category_id' => $portfolioTypeCategory->id, 
                    'code' => $type['code']
                ],
                [
                    'name' => $type['name'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
