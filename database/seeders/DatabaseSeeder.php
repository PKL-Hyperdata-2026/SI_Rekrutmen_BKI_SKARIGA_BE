<?php

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
            ClassSeeder::class,
            MajorSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            SelectionStageStandardTypeSeeder::class,
            JobApplicationStandardTypeSeeder::class,
            ApplicationStageHistoryStandardTypeSeeder::class,
            RecruitmentAttendanceStandardTypeSeeder::class,
            TracerStudyStandardTypeSeeder::class,
            PlacementStatusStandardTypeSeeder::class,
            StudentPortfolioStandartTypeSeeder::class,
            StudentAlumniSeeder::class,
            JobPlacementSeeder::class,
            JobVacancySeeder::class,
        ]);
    }
}
