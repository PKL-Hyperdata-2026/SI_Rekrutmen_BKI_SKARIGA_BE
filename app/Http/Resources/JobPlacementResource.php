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
            'id' => encrypt($this->id),
            'jobApplicationId' => $this->job_application_id ? encrypt($this->job_application_id) : null,
            'jobApplication' => $this->whenLoaded('jobApplication', function () {
                if (! $this->jobApplication) {
                    return null;
                }

                return [
                    'id' => encrypt($this->jobApplication->id),
                    'jobVacancyId' => $this->job_application->job_vacancy_id ? encrypt($this->job_application->job_vacancy_id) : null,
                    'jobVacancy' => $this->jobApplication->relationLoaded('jobVacancy') && $this->jobApplication->jobVacancy ? [
                        'id' => encrypt($this->jobApplication->jobVacancy->id),
                        'title' => $this->jobApplication->jobVacancy->title,
                        'position' => $this->jobApplication->jobVacancy->position,
                    ] : null,
                ];
            }),
            'studentAlumniId' => $this->student_alumni_id ? encrypt($this->student_alumni_id) : null,
            'studentAlumni' => $this->whenLoaded('studentAlumni', function () {
                if (! $this->studentAlumni) {
                    return null;
                }

                return [
                    'id' => encrypt($this->studentAlumni->id),
                    'nis' => $this->studentAlumni->nis,
                    'user' => $this->studentAlumni->relationLoaded('user') && $this->studentAlumni->user ? [
                        'id' => encrypt($this->studentAlumni->user->id),
                        'fullName' => $this->studentAlumni->user->full_name,
                        'email' => $this->studentAlumni->user->email,
                        'phone' => $this->studentAlumni->user->phone,
                    ] : null,
                    'major' => $this->studentAlumni->relationLoaded('major') ? $this->studentAlumni->major?->name : null,
                ];
            }),
            'companyId' => $this->company_id ? encrypt($this->company_id) : null,
            'company' => $this->whenLoaded('company', function () {
                if (! $this->company) {
                    return null;
                }

                return [
                    'id' => encrypt($this->company->id),
                    'name' => $this->company->name,
                    'address' => $this->company->address,
                ];
            }),
            'placementStatusId' => $this->placement_status_id ? encrypt($this->placement_status_id) : null,
            'placementStatus' => $this->whenLoaded('placementStatus', function () {
                if (! $this->placementStatus) {
                    return null;
                }

                return [
                    'id' => encrypt($this->placementStatus->id),
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
