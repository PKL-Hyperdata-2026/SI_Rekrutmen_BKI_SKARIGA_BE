<?php

namespace Database\Seeders;

use App\Models\JobPlacement;
use App\Models\StudentAlumni;
use App\Models\Company;
use App\Models\StandardType;
use Illuminate\Database\Seeder;

class JobPlacementSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            'PT Telkom Indonesia',
            'PT Astra Honda Motor',
            'Gojek Tokopedia (GoTo)',
            'PT PLN (Persero)',
            'Shopee Indonesia'
        ];

        $companyIds = [];
        foreach ($companies as $companyName) {
            $company = Company::firstOrCreate(
                ['name' => $companyName],
                [
                    'email' => strtolower(str_replace([' ', '(', ')'], ['', '', ''], $companyName)) . '@example.com',
                    'is_active' => true
                ]
            );
            $companyIds[] = $company->id;
        }

        $placementStatus = StandardType::byCategory('placement_status')
            ->where('code', 'placement_12_bulan')
            ->first();

        $students = StudentAlumni::whereNotNull('graduation_year')
            ->orderBy('id', 'asc')
            ->take(20)
            ->get();

        foreach ($students as $index => $student) {
            JobPlacement::factory()->create([
                'student_alumni_id' => $student->id,
                'company_id' => $companyIds[$index % count($companyIds)],
                'placement_status_id' => $placementStatus?->id,
                'accepted_date' => '2024-01-0' . (($index % 9) + 1),
                'start_date' => '2024-02-0' . (($index % 9) + 1),
                'notes' => 'Penempatan batch ' . (($index % 2) + 1),
            ]);
        }
    }
}
