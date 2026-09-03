<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\SocialMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentMyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'nis' => $this->nis ?? '',
            'fullName' => $this->user?->full_name ?? '',
            'email' => $this->user?->email ?? '',
            'phone' => $this->user?->phone ?? '',
            'role' => $this->user?->role ?? 'siswa',
            'status' => strtoupper($this->user?->role ?? 'SISWA'),
            'classId' => $this->class_id,
            'majorId' => $this->major_id,
            'employmentStatusId' => $this->employment_status_id,
            'graduationYear' => $this->graduation_year,
            'socialMedia' => SocialMedia::normalize($this->social_media),
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'class' => $this->whenLoaded('class', function () {
                return $this->class ? [
                    'id' => $this->class->id,
                    'code' => $this->class->code,
                    'name' => $this->class->name,
                ] : null;
            }),
            'major' => $this->whenLoaded('major', function () {
                return $this->major ? [
                    'id' => $this->major->id,
                    'code' => $this->major->code,
                    'name' => $this->major->name,
                ] : null;
            }),
            'employmentStatus' => $this->whenLoaded('employmentStatus', function () {
                return $this->employmentStatus ? [
                    'id' => $this->employmentStatus->id,
                    'code' => $this->employmentStatus->code,
                    'name' => $this->employmentStatus->name,
                ] : null;
            }),
            'portfolios' => StudentPortfolioResource::collection($this->whenLoaded('portfolios')),
        ];
    }
}
