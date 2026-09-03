<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationStageHistoryResource extends JsonResource
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
            'stage' => $this->whenLoaded('selectionStage', function () {
                return $this->selectionStage ? [
                    'id' => $this->selectionStage->id,
                    'name' => $this->selectionStage->name,
                    'order' => $this->selectionStage->sequence_order,
                ] : null;
            }),
            'status' => $this->whenLoaded('status', function () {
                return $this->status ? [
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'code' => $this->status->code,
                ] : null;
            }),
            'assessorName' => $this->relationLoaded('assessor') ? $this->assessor?->full_name : null,
            'score' => $this->score !== null ? (float) $this->score : null,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
