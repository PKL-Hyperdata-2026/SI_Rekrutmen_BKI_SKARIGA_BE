<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobPlacement;
use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use App\Models\StudentAlumni;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPlacement>
 */
class JobPlacementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_application_id' => null, // Opsional
            'student_alumni_id' => StudentAlumni::factory(),
            'company_id' => Company::factory(),
            'placement_status_id' => function () {
                $type = StandardType::byCategory('placement_status')->inRandomOrder()->first();
                if (!$type) {
                    $category = StandardTypeCategory::firstOrCreate(['code' => 'placement_status'], ['name' => 'Placement Status']);
                    $type = StandardType::create([
                        'category_id' => $category->id,
                        'code' => 'placement_12_bulan',
                        'name' => 'Penempatan 12 Bulan',
                        'is_active' => true,
                    ]);
                }
                return $type->id;
            },
            'accepted_date' => fake('id_ID')->dateTimeBetween('-1 year', 'now'),
            'start_date' => fake('id_ID')->dateTimeBetween('now', '+1 month'),
            'notes' => fake('id_ID')->sentence(),
        ];
    }
}
