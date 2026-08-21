<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_alumni_id' => ['sometimes', 'required', 'integer', 'exists:students_alumni,id'],
            'company_id' => ['sometimes', 'required', 'integer', 'exists:companies,id'],
            'job_application_id' => ['sometimes', 'nullable', 'integer', 'exists:job_applications,id'],
            'placement_status_id' => ['sometimes', 'nullable', 'integer', 'exists:standard_types,id'],
            'accepted_date' => ['sometimes', 'nullable', 'date'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_alumni_id.required' => 'Data siswa atau alumni wajib dipilih.',
            'student_alumni_id.exists' => 'Data siswa atau alumni tidak ditemukan.',
            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.exists' => 'Perusahaan yang dipilih tidak valid.',
            'job_application_id.exists' => 'Lamaran pekerjaan yang dipilih tidak valid.',
            'placement_status_id.exists' => 'Status penempatan yang dipilih tidak valid.',
            'accepted_date.date' => 'Format tanggal diterima tidak valid.',
            'start_date.date' => 'Format tanggal mulai kerja tidak valid.',
        ];
    }
}
