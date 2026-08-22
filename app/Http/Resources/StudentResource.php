<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'nis' => $this->nis,
            'fullName' => $this->user?->full_name,
            'email' => $this->user?->email,
            'phone' => $this->user?->phone,
            'classId' => $this->class_id,
            'majorId' => $this->major_id,
            'employmentStatusId' => $this->employment_status_id,
            'currentCompanyId' => $this->current_company_id,
            'graduationYear' => $this->graduation_year,
            'socialMedia' => $this->social_media,
            'currentPosition' => $this->current_position,
            'startingSalary' => $this->starting_salary,
            'waitingTimeMonths' => $this->waiting_time_months,
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'fullName' => $this->user->full_name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'role' => $this->user->role,
                    'isActive' => (bool) $this->user->is_active,
                ] : null;
            }),
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
            'currentCompany' => $this->whenLoaded('currentCompany', function () {
                return $this->currentCompany ? [
                    'id' => $this->currentCompany->id,
                    'name' => $this->currentCompany->name,
                ] : null;
            }),
            'portfolios' => $this->whenLoaded('portfolios', function () {
                return $this->portfolios->map(function ($portfolio) {
                    return [
                        'id' => $portfolio->id,
                        'studentAlumniId' => $portfolio->student_alumni_id,
                        'categoryId' => $portfolio->category_id,
                        'category' => $portfolio->category ? [
                            'id' => $portfolio->category->id,
                            'code' => $portfolio->category->code,
                            'name' => $portfolio->category->name,
                        ] : null,
                        'title' => $portfolio->title,
                        'description' => $portfolio->description,
                        'filePath' => $portfolio->file_path,
                        'fileUrl' => $portfolio->file_path ? Storage::disk('public')->url($portfolio->file_path) : null,
                        'createdAt' => $portfolio->created_at?->toIso8601String(),
                    ];
                });
            }),
        ];
    }
}
