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
            'vacancy' => $this->whenLoaded('jobVacancy', function () {
                return $this->jobVacancy ? [
                    'id' => $this->jobVacancy->id,
                    'title' => $this->jobVacancy->title,
                    'companyName' => $this->jobVacancy->company?->name,
                    'companyLogo' => $this->jobVacancy->company?->logo_path,
                    'jobType' => $this->jobVacancy->jobType?->name,
                    'location' => $this->jobVacancy->work_location,
                ] : null;
            }),
            'status' => $this->whenLoaded('status', function () {
                return $this->status ? [
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'code' => $this->status->code,
                ] : null;
            }),
            'currentStage' => $this->whenLoaded('currentStage', function () {
                return $this->currentStage ? [
                    'id' => $this->currentStage->id,
                    'name' => $this->currentStage->name,
                ] : null;
            }),
            'appliedAt' => $this->applied_at?->toIso8601String(),
            'notes' => $this->notes,
            'stageHistories' => ApplicationStageHistoryResource::collection($this->whenLoaded('stageHistories')),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
