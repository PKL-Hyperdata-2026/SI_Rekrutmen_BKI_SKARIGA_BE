<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrdTestScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $scheduledAt = $this->scheduled_at;
        $isPast = $scheduledAt && $scheduledAt->isPast();
        $sessionStatus = $isPast ? 'Selesai' : 'Siap Dilaksanakan';
        $sessionStatusCode = $isPast ? 'completed' : 'ready';

        return [
            'id' => encrypt($this->id),
            'jobVacancyId' => $this->job_vacancy_id ? encrypt($this->job_vacancy_id) : null,
            'jobVacancy' => $this->whenLoaded('jobVacancy', function () {
                if (! $this->jobVacancy) {
                    return null;
                }

                return [
                    'id' => encrypt($this->jobVacancy->id),
                    'title' => $this->jobVacancy->title,
                    'position' => $this->jobVacancy->position,
                ];
            }),
            'stageTypeId' => $this->stage_type_id ? encrypt($this->stage_type_id) : null,
            'stageType' => $this->whenLoaded('stageType', function () {
                if (! $this->stageType) {
                    return null;
                }

                return [
                    'id' => encrypt($this->stageType->id),
                    'name' => $this->stageType->name,
                    'code' => $this->stageType->code,
                    'metadata' => $this->stageType->metadata,
                ];
            }),
            'name' => $this->name,
            'sequenceOrder' => $this->sequence_order,
            'description' => $this->description,
            'minimumScore' => $this->minimum_score !== null ? (float) $this->minimum_score : null,
            'scheduledAt' => $scheduledAt?->toIso8601String(),
            'scheduledAtFormatted' => $scheduledAt ? $scheduledAt->translatedFormat('d M Y • H:i').' WIB' : null,
            'scheduledDate' => $scheduledAt?->format('Y-m-d'),
            'scheduledTime' => $scheduledAt?->format('H:i'),
            'location' => $this->location,
            'sessionStatus' => $sessionStatus,
            'sessionStatusCode' => $sessionStatusCode,
            'totalParticipants' => $this->total_participants ?? ($this->relationLoaded('stageHistories') ? $this->stageHistories->count() : 0),
            'hasScores' => (bool) ($this->has_scores ?? false),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
