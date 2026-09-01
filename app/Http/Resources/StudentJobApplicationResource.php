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
                    'company_name' => $this->jobVacancy->company?->name,
                    'company_logo' => $this->jobVacancy->company?->logo_path,
                    'job_type' => $this->jobVacancy->jobType?->name,
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
            'current_stage' => $this->whenLoaded('currentStage', function () {
                return $this->currentStage ? [
                    'id' => $this->currentStage->id,
                    'name' => $this->currentStage->name,
                ] : null;
            }),
            'applied_at' => $this->applied_at?->toIso8601String(),
            'notes' => $this->notes,
            'stage_histories' => ApplicationStageHistoryResource::collection($this->whenLoaded('stageHistories')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
