<?php

namespace Database\Seeders;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use Illuminate\Database\Seeder;

class JobApplicationStandardTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Category: job_application_status
        $statusCategory = StandardTypeCategory::firstOrCreate(
            ['code' => 'job_application_status'],
            ['name' => 'Status Lamaran Kerja', 'description' => 'Alur status utama berkas lamaran kerja pelamar']
        );

        $statuses = [
            ['code' => 'pending', 'name' => 'Menunggu Review (Pending)', 'metadata' => ['badge_color' => 'gray', 'icon' => 'clock'], 'sort_order' => 1],
            ['code' => 'in_progress', 'name' => 'Diproses (In Progress)', 'metadata' => ['badge_color' => 'blue', 'icon' => 'refresh-cw'], 'sort_order' => 2],
            ['code' => 'accepted', 'name' => 'Diterima (Accepted)', 'metadata' => ['badge_color' => 'green', 'icon' => 'check-circle'], 'sort_order' => 3],
            ['code' => 'rejected', 'name' => 'Ditolak (Rejected)', 'metadata' => ['badge_color' => 'red', 'icon' => 'x-circle'], 'sort_order' => 4],
        ];

        foreach ($statuses as $item) {
            StandardType::updateOrCreate(
                ['category_id' => $statusCategory->id, 'code' => $item['code']],
                ['name' => $item['name'], 'metadata' => $item['metadata'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }
    }
}
