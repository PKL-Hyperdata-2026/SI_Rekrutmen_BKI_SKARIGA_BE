<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SelectionStageSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'name' => $this->resource['name'],
            'sequenceOrder' => $this->resource['sequence_order'] ?? 0,
            'participantCount' => $this->resource['participant_count'] ?? 0,
            'stageIds' => array_values($this->resource['stage_ids'] ?? []),
        ];
    }
}
