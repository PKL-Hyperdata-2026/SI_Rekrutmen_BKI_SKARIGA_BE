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
        $isAdmin = $request->is('api/admin/*');

        return [
            'id'                => $isAdmin ? encrypt($this->id) : $this->id,
            'studentAlumniId'   => $isAdmin ? encrypt($this->student_alumni_id) : $this->student_alumni_id,
            'careerStatus'      => $this->career_status,

            // Detail Bekerja & Penempatan
            'companyName'       => $this->company_name,
            'companySector'     => $this->company_sector,
            'jobTitle'          => $this->job_title,
            'jobLocation'       => $this->job_location,
            'minimumSalary'     => $this->minimum_salary,
            'maximumSalary'     => $this->maximum_salary,
            'waitingPeriod'     => $this->waiting_period,
            'acceptedDate'      => $this->accepted_date?->format('Y-m-d'),
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

            // Status 12 Bulan (Evaluasi Karir/Penempatan)
            'status12Bulan'     => $this->status_12_bulan,

            // Relasi Alumni & Siswa
            'studentAlumni'     => $this->whenLoaded('studentAlumni', function () use ($isAdmin) {
                return [
                    'id'             => $isAdmin ? encrypt($this->studentAlumni->id) : $this->studentAlumni->id,
                    'nis'            => $this->studentAlumni->nis,
                    'graduationYear' => $this->studentAlumni->graduation_year,
                    'fullName'       => $this->studentAlumni->user?->full_name,
                    'email'          => $this->studentAlumni->user?->email,
                    'phone'          => $this->studentAlumni->user?->phone,
                    'major'          => $this->studentAlumni->major ? [
                        'id'   => $isAdmin ? encrypt($this->studentAlumni->major->id) : $this->studentAlumni->major->id,
                        'name' => $this->studentAlumni->major->name,
                        'code' => $this->studentAlumni->major->code,
                    ] : null,
                    'class'          => $this->studentAlumni->class ? [
                        'id'   => $isAdmin ? encrypt($this->studentAlumni->class->id) : $this->studentAlumni->class->id,
                        'name' => $this->studentAlumni->class->name,
                        'code' => $this->studentAlumni->class->code,
                    ] : null,
                ];
            }),

            // Timestamps
            'createdAt'         => $this->created_at?->toIso8601String(),
            'updatedAt'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
