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
            'id'                  => $this->id,
            'student_alumni_id'   => $this->student_alumni_id,
            'career_status'       => $this->career_status,

            // Detail Bekerja
            'company_name'        => $this->company_name,
            'job_title'           => $this->job_title,
            'minimum_salary'      => $this->minimum_salary,
            'maximum_salary'      => $this->maximum_salary,
            'waiting_period'      => $this->waiting_period,
            'start_date'          => $this->start_date?->format('Y-m-d'),

            // Detail Wirausaha
            'business_name'       => $this->business_name,
            'business_address'    => $this->business_address,
            'instagram_handle'    => $this->instagram_handle,
            'average_income'      => $this->average_income,
            'business_field'      => $this->business_field,
            'business_start_date' => $this->business_start_date?->format('Y-m-d'),

            // Detail Lanjut Studi
            'university_name'     => $this->university_name,
            'study_program'       => $this->study_program,

            // Timestamps
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
