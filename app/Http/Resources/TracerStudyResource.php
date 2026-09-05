<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TracerStudyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'studentAlumniId'   => $this->student_alumni_id,
            'careerStatus'      => $this->career_status,

            // Detail Bekerja
            'companyName'       => $this->company_name,
            'jobTitle'          => $this->job_title,
            'minimumSalary'     => $this->minimum_salary,
            'maximumSalary'     => $this->maximum_salary,
            'waitingPeriod'     => $this->waiting_period,
            'startDate'         => $this->start_date?->format('Y-m-d'),

            // Detail Wirausaha
            'businessName'      => $this->business_name,
            'businessAddress'   => $this->business_address,
            'instagramAccount'  => $this->instagram_handle,
            'averageRevenue'    => $this->average_income,
            'businessField'     => $this->business_field,
            'businessStartDate' => $this->business_start_date?->format('Y-m-d'),

            // Detail Lanjut Studi
            'universityName'    => $this->university_name,
            'studyProgram'      => $this->study_program,

            // Timestamps
            'createdAt'         => $this->created_at?->toIso8601String(),
            'updatedAt'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
