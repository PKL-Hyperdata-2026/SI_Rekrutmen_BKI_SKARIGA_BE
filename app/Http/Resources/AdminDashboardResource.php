<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metrics = $this->resource['metrics'] ?? [];

        return [
            'metrics' => [
                'activeStudents' => $metrics['active_students'] ?? 0,
                'totalAlumni' => $metrics['total_alumni'] ?? 0,
                'activeVacancies' => $metrics['active_vacancies'] ?? 0,
                'applicantsThisMonth' => $metrics['applicants_this_month'] ?? 0,
                'placedWorkers' => $metrics['placed_workers'] ?? 0,
                'absorptionRate' => $metrics['absorption_rate'] ?? 0.0,
            ],
            'recruitmentChart' => $this->resource['recruitment_chart'] ?? [],
            'departmentDistribution' => $this->resource['department_distribution'] ?? [],
        ];
    }
}
