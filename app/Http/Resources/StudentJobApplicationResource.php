<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentJobApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jobVacancyId' => $this->job_vacancy_id,
            'vacancy' => $this->whenLoaded('jobVacancy', fn() => [
                'id' => $this->jobVacancy->id,
                'title' => $this->jobVacancy->title,
                'position' => $this->jobVacancy->position,
                'companyName' => $this->jobVacancy->company?->name,
                'workLocation' => $this->jobVacancy->work_location,
                'deadline' => $this->jobVacancy->deadline?->format('Y-m-d')
            ]),
            'status' => $this->whenLoaded('status', fn() => ['id' => $this->status->id, 'code' => $this->status->code, 'name' => $this->status->name]),
            'appliedAt' => $this->applied_at?->toIso8601String(),
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
