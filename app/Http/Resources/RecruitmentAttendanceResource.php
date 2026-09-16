<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stageHistory = $this->stageHistory;
        $jobApplication = $stageHistory?->jobApplication;
        $studentAlumni = $jobApplication?->studentAlumni;
        $user = $studentAlumni?->user;
        $jobVacancy = $jobApplication?->jobVacancy;
        $selectionStage = $stageHistory?->selectionStage;

        return [
            'id' => encrypt((string) $this->id),
            'applicant' => [
                'id' => $studentAlumni?->id !== null ? encrypt((string) $studentAlumni->id) : null,
                'name' => $user?->full_name,
                'nis' => $studentAlumni?->nis,
                'phone' => $user?->phone,
                'majorCode' => $studentAlumni?->major?->code,
                'majorName' => $studentAlumni?->major?->name,
                'graduationYear' => $studentAlumni?->graduation_year,
            ],
            'vacancy' => [
                'id' => $jobVacancy?->id !== null ? encrypt((string) $jobVacancy->id) : null,
                'title' => $jobVacancy?->title,
                'companyName' => $jobVacancy?->company?->name,
            ],
            'stage' => [
                'id' => $selectionStage?->id !== null ? encrypt((string) $selectionStage->id) : null,
                'name' => $selectionStage?->name,
                'sequenceOrder' => $selectionStage?->sequence_order,
                'scheduledAt' => $selectionStage?->scheduled_at?->toIso8601String(),
            ],
            'attendedAt' => $this->attended_at?->toIso8601String(),
            'validation' => [
                'status' => $this->validation_status,
                'validatedAt' => $this->validated_at?->toIso8601String(),
                'validatedByName' => $this->validator?->full_name,
                'notes' => $this->notes,
                'systemAction' => $this->system_action,
            ],
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
