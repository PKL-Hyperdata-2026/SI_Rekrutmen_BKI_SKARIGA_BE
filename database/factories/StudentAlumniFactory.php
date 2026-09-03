<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use App\Models\StudentAlumni;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAlumni>
 */
class StudentAlumniFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'major_id' => function () {
                $major = Major::first();

                if (! $major) {
                    $major = Major::firstOrCreate(
                        ['code' => 'RPL'],
                        ['name' => 'Rekayasa Perangkat Lunak', 'description' => null, 'is_active' => true]
                    );
                }

                return $major->id;
            },
            'employment_status_id' => fn () => $this->lookupId('employment_status', 'mencari_kerja', 'Mencari Kerja'),
            'class_id' => fn () => $this->lookupId('class', 'xii_rpl_1', 'XII RPL 1'),
            'nis' => fake()->unique()->numerify('########'),
            'graduation_year' => fake()->optional(0.7)->year(),
            'social_media' => ['linkedin' => 'https://linkedin.com/in/'.fake()->userName()],
            'current_company_id' => Company::factory(),
            'current_position' => fake()->jobTitle(),
            'starting_salary' => fake()->optional()->randomFloat(2, 3000000, 12000000),
            'waiting_time_months' => fake()->optional()->numberBetween(1, 12),
            'is_active' => true,
        ];
    }

    private function lookupId(string $categoryCode, string $fallbackCode, string $fallbackName): int
    {
        $type = StandardType::byCategory($categoryCode)->inRandomOrder()->first();

        if (! $type) {
            $category = StandardTypeCategory::firstOrCreate(
                ['code' => $categoryCode],
                ['name' => ucfirst(str_replace('_', ' ', $categoryCode)), 'description' => null]
            );
            $type = StandardType::create([
                'category_id' => $category->id,
                'code' => $fallbackCode,
                'name' => $fallbackName,
                'is_active' => true,
            ]);
        }

        return $type->id;
    }
}
