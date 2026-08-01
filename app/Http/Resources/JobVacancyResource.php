<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobVacancyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company' => $this->whenLoaded('company'),
            'job_type' => $this->whenLoaded('jobType'),
            'status' => $this->whenLoaded('status'),
            'target_applicant' => $this->whenLoaded('targetApplicant'),
            'title' => $this->title,
            'slug' => $this->slug,
            'position' => $this->position,
            'description' => $this->description,
            'qualification' => $this->qualification,
            'quota' => $this->quota,
            'deadline' => $this->deadline?->format('Y-m-d'),
            'work_location' => $this->work_location,
            'min_salary' => $this->min_salary,
            'max_salary' => $this->max_salary,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'majors' => $this->whenLoaded('majors'),
            'created_by_user' => $this->whenLoaded('createdBy'),
            'updated_by_user' => $this->whenLoaded('updatedBy'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
