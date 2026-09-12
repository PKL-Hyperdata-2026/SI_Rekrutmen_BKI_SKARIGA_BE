<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentJobApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
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
                'companyLogo' => $this->jobVacancy->company?->logo_path ? (
                    filter_var($this->jobVacancy->company->logo_path, FILTER_VALIDATE_URL)
                        ? $this->jobVacancy->company->logo_path
                        : asset('storage/' . $this->jobVacancy->company->logo_path)
                ) : null,
                'workLocation' => $this->jobVacancy->work_location,
                'deadline' => $this->jobVacancy->deadline?->format('Y-m-d')
            ]),
            'status' => $this->whenLoaded('status', fn() => ['id' => $this->status->id, 'code' => $this->status->code, 'name' => $this->status->name]),
            'currentStage' => $this->whenLoaded('currentStage', fn() => [
                'id' => $this->currentStage->id,
                'name' => $this->currentStage->name,
                'order' => $this->currentStage->sequence_order,
                'scheduledAt' => $this->currentStage->scheduled_at?->toIso8601String(),
                'location' => $this->currentStage->location,
                'instructions' => $this->currentStage->description,
            ]),
            'appliedAt' => $this->applied_at?->toIso8601String(),
            'notes' => $this->notes,
            'placement' => $this->whenLoaded('jobPlacement', fn() => $this->jobPlacement ? [
                'id' => $this->jobPlacement->id,
                'acceptedDate' => $this->jobPlacement->accepted_date?->format('Y-m-d'),
                'startDate' => $this->jobPlacement->start_date?->format('Y-m-d'),
                'notes' => $this->jobPlacement->notes,
            ] : null),
            'selectionResult' => $this->whenLoaded('selectionResult', fn() => $this->selectionResult ? [
                'id' => $this->selectionResult->id,
                'decision' => $this->selectionResult->decision,
            ] : null),
            'stageHistories' => ApplicationStageHistoryResource::collection($this->whenLoaded('stageHistories')),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
