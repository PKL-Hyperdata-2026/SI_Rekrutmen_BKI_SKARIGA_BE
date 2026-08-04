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
            EmploymentStatusSeeder::class,
            ClassSeeder::class,
            JobVacancyStandardTypeSeeder::class,
            
        ]);
    }
}
