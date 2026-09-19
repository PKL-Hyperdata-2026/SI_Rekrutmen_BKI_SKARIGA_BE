<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentSelectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => encrypt($this->id),
            'jobVacancyId' => $this->job_vacancy_id ? encrypt($this->job_vacancy_id) : null,
            'jobVacancy' => $this->whenLoaded('jobVacancy', function () {
                return [
                    'id' => encrypt($this->jobVacancy->id),
                    'title' => $this->jobVacancy->title,
                    'position' => $this->jobVacancy->position,
                    'companyName' => $this->jobVacancy->company?->name,
                    'quota' => $this->jobVacancy->quota,
                    'qualification' => $this->jobVacancy->qualification,
                    'description' => $this->jobVacancy->description,
                    'workLocation' => $this->jobVacancy->work_location,
                    'deadline' => $this->jobVacancy->deadline?->toIso8601String(),
                    'minSalary' => $this->jobVacancy->min_salary !== null ? (float) $this->jobVacancy->min_salary : null,
                    'maxSalary' => $this->jobVacancy->max_salary !== null ? (float) $this->jobVacancy->max_salary : null,
                ];
            }),
            'studentAlumni' => $this->whenLoaded('studentAlumni', function () {
                return [
                    'id' => encrypt($this->studentAlumni->id),
                    'nis' => $this->studentAlumni->nis,
                    'graduationYear' => $this->studentAlumni->graduation_year,
                    'user' => $this->studentAlumni->relationLoaded('user') && $this->studentAlumni->user ? [
                        'id' => encrypt($this->studentAlumni->user->id),
                        'fullName' => $this->studentAlumni->user->full_name,
                        'email' => $this->studentAlumni->user->email,
                        'phone' => $this->studentAlumni->user->phone,
                    ] : null,
                    'major' => $this->studentAlumni->relationLoaded('major') && $this->studentAlumni->major ? [
                        'id' => encrypt($this->studentAlumni->major->id),
                        'name' => $this->studentAlumni->major->name,
                        'code' => $this->studentAlumni->major->code,
                    ] : null,
                ];
            }),
            // Alias for frontend convenience (spec mentions studentAlumni.user.nama & major.nama)
            'student' => $this->whenLoaded('studentAlumni', function () {
                return [
                    'id' => encrypt($this->studentAlumni->id),
                    'name' => $this->studentAlumni->user?->full_name,
                    'nis' => $this->studentAlumni->nis,
                    'majorName' => $this->studentAlumni->major?->name,
                    'email' => $this->studentAlumni->user?->email,
                ];
            }),
            'currentStage' => $this->whenLoaded('currentStage', function () {
                return $this->currentStage ? [
                    'id' => encrypt($this->currentStage->id),
                    'name' => $this->currentStage->name,
                    'sequenceOrder' => $this->currentStage->sequence_order,
                    'scheduledAt' => $this->currentStage->scheduled_at?->toIso8601String(),
                    'location' => $this->currentStage->location,
                ] : null;
            }),
            'status' => $this->whenLoaded('status', function () {
                return $this->status ? [
                    'id' => encrypt($this->status->id),
                    'code' => $this->status->code,
                    'name' => $this->status->name,
                    'metadata' => $this->status->metadata,
                ] : null;
            }),
            'selectionResult' => $this->whenLoaded('selectionResult', function () {
                return $this->selectionResult ? [
                    'id' => encrypt($this->selectionResult->id),
                    'adminSelectionStatus' => $this->selectionResult->admin_selection_status,
                    'decision' => $this->selectionResult->decision,
                    'status' => $this->selectionResult->status,
                    'psychotestScore' => $this->selectionResult->psychotest_score !== null ? (float) $this->selectionResult->psychotest_score : null,
                    'interviewScore' => $this->selectionResult->interview_score !== null ? (float) $this->selectionResult->interview_score : null,
                    'mcuScore' => $this->selectionResult->mcu_score !== null ? (float) $this->selectionResult->mcu_score : null,
                    'finalScore' => $this->selectionResult->final_score !== null ? (float) $this->selectionResult->final_score : null,
                    'notes' => $this->selectionResult->notes,
                ] : null;
            }),
            'stageHistories' => $this->whenLoaded('stageHistories', function () {
                return $this->stageHistories->map(function ($history) {
                    $attendance = $history->relationLoaded('attendance') ? $history->attendance : null;
                    $attendanceStatus = $attendance && $attendance->relationLoaded('attendanceStatus') ? $attendance->attendanceStatus : null;

                    // Derive human label for attendance status; fallback to 'Belum Presensi'
                    $attendanceLabel = 'Belum Presensi';
                    if ($attendanceStatus) {
                        $attendanceLabel = $attendanceStatus->name;
                    } elseif ($attendance && $attendance->attended_at) {
                        $attendanceLabel = 'Hadir';
                    }

                    return [
                        'id' => encrypt($history->id),
                        'selectionStage' => $history->relationLoaded('selectionStage') && $history->selectionStage ? [
                            'id' => encrypt($history->selectionStage->id),
                            'name' => $history->selectionStage->name,
                            'sequenceOrder' => $history->selectionStage->sequence_order,
                            'scheduledAt' => $history->selectionStage->scheduled_at?->toIso8601String(),
                            'location' => $history->selectionStage->location,
                        ] : null,
                        'status' => $history->relationLoaded('status') && $history->status ? [
                            'id' => encrypt($history->status->id),
                            'code' => $history->status->code,
                            'name' => $history->status->name,
                        ] : null,
                        'score' => $history->score !== null ? (float) $history->score : null,
                        'notes' => $history->notes,
                        'assessor' => $history->relationLoaded('assessor') && $history->assessor ? [
                            'id' => encrypt($history->assessor->id),
                            'fullName' => $history->assessor->full_name,
                        ] : null,
                        'attendance' => $attendance ? [
                            'id' => encrypt($attendance->id),
                            'attendanceStatusId' => $attendance->attendance_status_id ? encrypt($attendance->attendance_status_id) : null,
                            'attendanceStatus' => $attendanceStatus ? [
                                'id' => encrypt($attendanceStatus->id),
                                'code' => $attendanceStatus->code,
                                'name' => $attendanceStatus->name,
                            ] : null,
                            'attendanceLabel' => $attendanceLabel,
                            'attendedAt' => $attendance->attended_at?->toIso8601String(),
                        ] : null,
                        'createdAt' => $history->created_at?->toIso8601String(),
                    ];
                })->values();
            }),
            'appliedAt' => $this->applied_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
