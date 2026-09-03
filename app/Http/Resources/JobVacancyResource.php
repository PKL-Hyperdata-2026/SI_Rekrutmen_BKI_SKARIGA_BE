<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobVacancyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'email' => $this->company->email,
                    'phone' => $this->company->phone,
                    'address' => $this->company->address,
                    'website' => $this->company->website,
                    'logoPath' => $this->company->logo_path,
                ];
            }),
            'jobTypeId' => $this->job_type_id,
            'jobType' => $this->whenLoaded('jobType', function () {
                return $this->jobType ? [
                    'id' => $this->jobType->id,
                    'code' => $this->jobType->code,
                    'name' => $this->jobType->name,
                    'metadata' => $this->jobType->metadata,
                ] : null;
            }),
            'statusId' => $this->status_id,
            'status' => $this->whenLoaded('status', function () {
                return $this->status ? [
                    'id' => $this->status->id,
                    'code' => $this->status->code,
                    'name' => $this->status->name,
                    'metadata' => $this->status->metadata,
                ] : null;
            }),
            'targetApplicantId' => $this->target_applicant_id,
            'targetApplicant' => $this->whenLoaded('targetApplicant', function () {
                return $this->targetApplicant ? [
                    'id' => $this->targetApplicant->id,
                    'code' => $this->targetApplicant->code,
                    'name' => $this->targetApplicant->name,
                    'metadata' => $this->targetApplicant->metadata,
                ] : null;
            }),
            'title' => $this->title,
            'slug' => $this->slug,
            'position' => $this->position,
            'description' => $this->description,
            'qualification' => $this->qualification,
            'quota' => $this->quota,
            'deadline' => $this->deadline?->format('Y-m-d'),
            'workLocation' => $this->work_location,
            'majors' => $this->whenLoaded('majors', function () {
                return $this->majors->map(function ($major) {
                    return [
                        'id' => $major->id,
                        'code' => $major->code,
                        'name' => $major->name,
                    ];
                });
            }),
            'majorIds' => $this->whenLoaded('majors', function () {
                return $this->majors->pluck('id');
            }),
            'minSalary' => $this->min_salary,
            'maxSalary' => $this->max_salary,
            'isFeatured' => (bool) $this->is_featured,
            'isActive' => (bool) $this->is_active,
            'createdByUser' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? [
                    'id' => $this->createdBy->id,
                    'fullName' => $this->createdBy->full_name,
                    'email' => $this->createdBy->email,
                ] : null;
            }),
            'updatedByUser' => $this->whenLoaded('updatedBy', function () {
                return $this->updatedBy ? [
                    'id' => $this->updatedBy->id,
                    'fullName' => $this->updatedBy->full_name,
                    'email' => $this->updatedBy->email,
                ] : null;
            }),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
