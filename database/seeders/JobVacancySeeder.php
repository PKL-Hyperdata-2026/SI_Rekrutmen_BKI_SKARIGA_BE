<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobVacancySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::orderBy('id')->take(5)->get();
        if ($companies->isEmpty())
            return;

        $rpl = Major::where('code', 'RPL')->first();

        $status = StandardType::byCategory('vacancy_status')->where('code', 'published')->first();
        $jobType = StandardType::byCategory('job_type')->where('code', 'full_time')->first();
        $target = StandardType::byCategory('target_applicant')->where('code', 'alumni_only')->first();

        $jobPositions = [
            'Junior Web Developer',
            'Network Engineer',
            'Frontend Developer',
            'Backend Developer',
            'UI/UX Designer',
            'IT Support',
            'System Administrator',
            'DevOps Engineer',
            'Data Analyst',
            'Mobile App Developer',
            'QA Tester',
            'IT Security',
            'Software Engineer',
            'Fullstack Developer',
            'Database Admin',
            'IT Helpdesk',
            'Cloud Engineer',
            'IT Manager',
            'Business Analyst',
            'Technical Writer'
        ];

        $locations = ['Surabaya', 'Jakarta', 'Malang', 'Sidoarjo', 'Bandung'];

        foreach ($jobPositions as $index => $position) {
            $company = $companies[$index % $companies->count()];
            $title = 'Lowongan ' . $position;
            $location = $locations[$index % count($locations)];
            $baseSalary = (4 + ($index % 3)) * 1000000;

            $job = JobVacancy::create([
                'company_id' => $company->id,
                'job_type_id' => $jobType?->id,
                'status_id' => $status?->id,
                'target_applicant_id' => $target?->id,
                'title' => $title,
                'slug' => Str::slug($title . '-' . $company->id . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT)),
                'position' => $position,
                'description' => 'Dibutuhkan kandidat handal untuk posisi ' . $position . ' di ' . $company->name,
                'qualification' => "1. Lulusan SMK/Sederajat\n2. Mampu bekerja dalam tim\n3. Domisili " . $location,
                'work_location' => $location,
                'min_salary' => $baseSalary,
                'max_salary' => $baseSalary + 2000000,
                'quota' => ($index % 5) + 2,
                'is_active' => true,
                'deadline' => '2024-12-31',
            ]);

            if ($rpl) {
                $job->majors()->attach($rpl->id);
            }
        }
    }
}

