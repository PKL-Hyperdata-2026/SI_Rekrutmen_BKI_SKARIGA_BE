<?php

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class CompanyIndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = StandardTypeCategory::firstOrCreate(
            ['code' => 'company_industry'],
            [
                'name' => 'Industri Perusahaan',
                'description' => 'Kategori bidang/sektor industri mitra perusahaan BKK',
            ]
        );

        $industries = [
            [
                'code' => 'software_house_it',
                'name' => 'Software House & Teknologi Informasi',
                'metadata' => ['icon' => 'code-slash', 'badge_color' => 'blue'],
                'sort_order' => 1,
            ],
            [
                'code' => 'manufacturing_automotive',
                'name' => 'Manufaktur & Otomotif',
                'metadata' => ['icon' => 'cog', 'badge_color' => 'orange'],
                'sort_order' => 2,
            ],
            [
                'code' => 'electronics_hardware',
                'name' => 'Elektronika & Hardware Komputer',
                'metadata' => ['icon' => 'cpu', 'badge_color' => 'cyan'],
                'sort_order' => 3,
            ],
            [
                'code' => 'telecommunication_networking',
                'name' => 'Telekomunikasi & Jaringan',
                'metadata' => ['icon' => 'wifi', 'badge_color' => 'purple'],
                'sort_order' => 4,
            ],
            [
                'code' => 'multimedia_creative_agency',
                'name' => 'Desain Kreatif, DKV & Multimedia',
                'metadata' => ['icon' => 'palette', 'badge_color' => 'pink'],
                'sort_order' => 5,
            ],
            [
                'code' => 'machinery_heavy_equipment',
                'name' => 'Teknik Mesin & Alat Berat',
                'metadata' => ['icon' => 'wrench', 'badge_color' => 'red'],
                'sort_order' => 6,
            ],
            [
                'code' => 'construction_property',
                'name' => 'Konstruksi & Properti',
                'metadata' => ['icon' => 'building', 'badge_color' => 'yellow'],
                'sort_order' => 7,
            ],
            [
                'code' => 'retail_fnb_hospitality',
                'name' => 'Retail, Perhotelan & F&B',
                'metadata' => ['icon' => 'store', 'badge_color' => 'green'],
                'sort_order' => 8,
            ],
            [
                'code' => 'finance_banking',
                'name' => 'Keuangan, Perbankan & Akuntansi',
                'metadata' => ['icon' => 'dollar-sign', 'badge_color' => 'emerald'],
                'sort_order' => 9,
            ],
            [
                'code' => 'other',
                'name' => 'Lainnya',
                'metadata' => ['icon' => 'folder', 'badge_color' => 'gray'],
                'sort_order' => 10,
            ],
        ];

        foreach ($industries as $industry) {
            StandardType::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'code' => $industry['code'],
                ],
                [
                    'name' => $industry['name'],
                    'metadata' => $industry['metadata'],
                    'sort_order' => $industry['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
