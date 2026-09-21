<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobApplication
 */
class HrdSelectionResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->studentAlumni;
        $user = $student?->user;
        $major = $student?->major;
        $vacancy = $this->jobVacancy;
        $result = $this->selectionResult;

        return [
            'id' => encrypt((string) $this->id),
            'applicationId' => encrypt((string) $this->id),
            'applicant' => [
                'id' => $student ? encrypt((string) $student->id) : null,
                'name' => $user?->full_name ?? '-',
                'nis' => $student?->nis ?? '-',
                'school' => 'SMK PGRI 1 GIRI',
                'majorName' => $major?->name ?? '-',
                'graduationYear' => $student?->graduation_year,
            ],
            'vacancy' => [
                'id' => $vacancy ? encrypt((string) $vacancy->id) : null,
                'title' => $vacancy?->title ?? '-',
                'position' => $vacancy?->position ?? '-',
            ],
            'adminSelectionStatus' => $result?->admin_selection_status ?? 'lolos',
            'psychotestScore' => $result?->psychotest_score !== null ? (float) $result->psychotest_score : null,
            'interviewScore' => $result?->interview_score !== null ? (float) $result->interview_score : null,
            'mcuScore' => $result?->mcu_score !== null ? (float) $result->mcu_score : null,
            'finalScore' => $result?->final_score !== null ? (float) $result->final_score : null,
            'decision' => $result?->decision ?? 'pending',
            'status' => $result?->status ?? 'draft',
            'notes' => $result?->notes,
            'letterPath' => $result?->letter_path,
            'letterUrl' => $result?->letter_path ? asset('storage/'.ltrim((string) $result->letter_path, '/')) : null,
            'appliedAt' => $this->applied_at?->toIso8601String(),
        ];
    }
}
