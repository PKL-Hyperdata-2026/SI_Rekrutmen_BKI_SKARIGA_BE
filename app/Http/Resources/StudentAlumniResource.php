<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentAlumniResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => encrypt($this->id),
            'userId' => $this->user_id ? encrypt($this->user_id) : null,
            'nis' => $this->nis,
            'fullName' => $this->user?->full_name,
            'email' => $this->user?->email,
            'phone' => $this->user?->phone,
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => encrypt($this->user->id),
                    'fullName' => $this->user->full_name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'role' => $this->user->role,
                    'isActive' => (bool) $this->user->is_active,
                ] : null;
            }),
            'majorId' => $this->major_id ? encrypt($this->major_id) : null,
            'major' => $this->whenLoaded('major', function () {
                return $this->major ? [
                    'id' => encrypt($this->major->id),
                    'code' => $this->major->code,
                    'name' => $this->major->name,
                ] : null;
            }),
            'classId' => $this->class_id ? encrypt($this->class_id) : null,
            'class' => $this->whenLoaded('class', function () {
                return $this->class ? [
                    'id' => encrypt($this->class->id),
                    'code' => $this->class->code,
                    'name' => $this->class->name,
                ] : null;
            }),
            'graduationYear' => $this->graduation_year,
            'employmentStatusId' => $this->employment_status_id ? encrypt($this->employment_status_id) : null,
            'employmentStatus' => $this->whenLoaded('employmentStatus', function () {
                return $this->employmentStatus ? [
                    'id' => encrypt($this->employmentStatus->id),
                    'code' => $this->employmentStatus->code,
                    'name' => $this->employmentStatus->name,
                ] : null;
            }),
            'currentCompanyId' => $this->current_company_id ? encrypt($this->current_company_id) : null,
            'currentCompany' => $this->whenLoaded('currentCompany', function () {
                return $this->currentCompany ? [
                    'id' => encrypt($this->currentCompany->id),
                    'name' => $this->currentCompany->name,
                ] : null;
            }),
            'currentPosition' => $this->current_position,
            'startingSalary' => $this->starting_salary,
            'waitingTimeMonths' => $this->waiting_time_months,
            'socialMedia' => $this->social_media,
            'isActive' => (bool) $this->is_active,
            'portfolios' => $this->whenLoaded('portfolios', function () {
                return $this->portfolios->map(function ($portfolio) {
                    return [
                        'id' => encrypt($portfolio->id),
                        'studentAlumniId' => $portfolio->student_alumni_id ? encrypt($portfolio->student_alumni_id) : null,
                        'categoryId' => $portfolio->category_id ? encrypt($portfolio->category_id) : null,
                        'category' => $portfolio->category ? [
                            'id' => encrypt($portfolio->category->id),
                            'code' => $portfolio->category->code,
                            'name' => $portfolio->category->name,
                        ] : null,
                        'title' => $portfolio->title,
                        'description' => $portfolio->description,
                        'fileName' => $portfolio->original_filename ?? ($portfolio->file_path ? basename($portfolio->file_path) : null),
                        'originalFilename' => $portfolio->original_filename,
                        'filePath' => $portfolio->file_path,
                        'fileUrl' => $portfolio->file_path ? Storage::disk('public')->url($portfolio->file_path) : null,
                        'createdAt' => $portfolio->created_at?->toIso8601String(),
                    ];
                });
            }),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
