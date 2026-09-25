<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ApplicationStageHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ApplicationStageHistory
 */
class HrdTestParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $application = $this->jobApplication;
        $student = $application?->studentAlumni;
        $user = $student?->user;
        $attendance = $this->attendance;

        $attendanceStatusName = $attendance?->attendanceStatus?->name ?? 'Belum Presensi';
        $attendanceStatusCode = 'not_attended';

        if ($attendance?->attended_at !== null) {
            $attendanceStatusName = 'Hadir';
            $attendanceStatusCode = 'attended';
        }

        return [
            'id' => encrypt($this->id),
            'stageHistoryId' => encrypt($this->id),
            'jobApplicationId' => $this->job_application_id ? encrypt($this->job_application_id) : null,
            'student' => [
                'id' => $student ? encrypt($student->id) : null,
                'name' => $user?->full_name ?? '-',
                'nis' => $student?->nis,
                'email' => $user?->email,
                'phone' => $user?->phone,
            ],
            'attendanceStatus' => $attendanceStatusName,
            'attendanceStatusCode' => $attendanceStatusCode,
            'attendedAt' => $attendance?->attended_at?->toIso8601String(),
            'score' => $this->score !== null ? (float) $this->score : null,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
