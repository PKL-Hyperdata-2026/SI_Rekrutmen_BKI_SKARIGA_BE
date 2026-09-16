<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**1
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CompanyIndustrySeeder::class,
            CompanySeeder::class,
            ClassSeeder::class,
            DepartmentSeeder::class,
            MajorSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
            TracerStudyStandardTypeSeeder::class,
            PlacementStatusStandardTypeSeeder::class,
            StudentPortfolioStandardTypeSeeder::class,
            StudentAlumniSeeder::class,
            JobPlacementSeeder::class,
            JobVacancySeeder::class,
        ]);
    }
}
