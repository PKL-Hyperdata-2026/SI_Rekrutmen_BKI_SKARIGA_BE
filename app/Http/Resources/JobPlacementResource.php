<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobPlacementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jobApplicationId' => $this->job_application_id,
            'jobApplication' => $this->whenLoaded('jobApplication', function () {
                if (! $this->jobApplication) {
                    return null;
                }

                return [
                    'id' => $this->jobApplication->id,
                    'jobVacancyId' => $this->jobApplication->job_vacancy_id,
                    'jobVacancy' => $this->jobApplication->relationLoaded('jobVacancy') && $this->jobApplication->jobVacancy ? [
                        'id' => $this->jobApplication->jobVacancy->id,
                        'title' => $this->jobApplication->jobVacancy->title,
                        'position' => $this->jobApplication->jobVacancy->position,
                    ] : null,
                ];
            }),
            'studentAlumniId' => $this->student_alumni_id,
            'studentAlumni' => $this->whenLoaded('studentAlumni', function () {
                if (! $this->studentAlumni) {
                    return null;
                }

                return [
                    'id' => $this->studentAlumni->id,
                    'nis' => $this->studentAlumni->nis,
                    'user' => $this->studentAlumni->relationLoaded('user') && $this->studentAlumni->user ? [
                        'id' => $this->studentAlumni->user->id,
                        'fullName' => $this->studentAlumni->user->full_name,
                        'email' => $this->studentAlumni->user->email,
                        'phone' => $this->studentAlumni->user->phone,
                    ] : null,
                    'major' => $this->studentAlumni->relationLoaded('major') ? $this->studentAlumni->major?->name : null,
                ];
            }),
            'companyId' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                if (! $this->company) {
                    return null;
                }

                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'address' => $this->company->address,
                ];
            }),
            'placementStatusId' => $this->placement_status_id,
            'placementStatus' => $this->whenLoaded('placementStatus', function () {
                if (! $this->placementStatus) {
                    return null;
                }

                return [
                    'id' => $this->placementStatus->id,
                    'code' => $this->placementStatus->code,
                    'name' => $this->placementStatus->name,
                    'metadata' => $this->placementStatus->metadata,
                ];
            }),
            'acceptedDate' => $this->accepted_date?->format('Y-m-d'),
            'startDate' => $this->start_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
