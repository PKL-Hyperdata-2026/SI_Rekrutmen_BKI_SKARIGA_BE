<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAlumniResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'nis' => $this->nis,
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'fullName' => $this->user->full_name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'role' => $this->user->role,
                    'isActive' => $this->user->is_active,
                ] : null;
            }),
            'majorId' => $this->major_id,
            'major' => $this->whenLoaded('major', fn () => $this->major?->name),
            'classId' => $this->class_id,
            'class' => $this->whenLoaded('class', fn () => $this->class?->name),
            'graduationYear' => $this->graduation_year,
            'employmentStatusId' => $this->employment_status_id,
            'employmentStatus' => $this->whenLoaded('employmentStatus', fn () => $this->employmentStatus?->name),
            'currentCompanyId' => $this->current_company_id,
            'currentCompany' => $this->whenLoaded('currentCompany', function () {
                return $this->currentCompany ? [
                    'id' => $this->currentCompany->id,
                    'name' => $this->currentCompany->name,
                ] : null;
            }),
            'currentPosition' => $this->current_position,
            'startingSalary' => $this->starting_salary,
            'waitingTimeMonths' => $this->waiting_time_months,
            'socialMedia' => $this->social_media,
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
