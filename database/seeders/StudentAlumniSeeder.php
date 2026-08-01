<?php

namespace Database\Seeders;

use App\Models\StudentAlumni;
use Illuminate\Database\Seeder;

class StudentAlumniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudentAlumni::factory(10)->create();
    }
}
