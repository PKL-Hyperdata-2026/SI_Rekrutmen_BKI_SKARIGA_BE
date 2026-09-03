<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StandardType;
use App\Models\StandardTypeCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'industry_id' => function () {
                $industry = StandardType::byCategory('company_industry')->inRandomOrder()->first();

                if (!$industry) {
                    $category = StandardTypeCategory::firstOrCreate(
                        ['code' => 'company_industry'],
                        ['name' => 'Company Industry']
                    );
                    $industry = StandardType::create([
                        'category_id' => $category->id,
                        'code' => 'tech',
                        'name' => 'Technology & IT',
                    ]);
                }

                return $industry->id;
            },
            'name' => 'PT ' . fake()->company(),
            'address' => fake()->address(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => 'https://www.' . fake()->domainName(),
            'pic_name' => fake()->name(),
            'pic_contact' => fake()->phoneNumber(),
            'logo_path' => null,
            'is_active' => true,
        ];
    }
}
