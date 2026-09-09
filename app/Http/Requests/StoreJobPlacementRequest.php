<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\JobPlacementService;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $companyId = app(JobPlacementService::class)->getCompanyIdByUserId($this->user()?->id);
        if ($companyId) {
            $this->merge(['company_id' => $companyId]);
        }
    }

    public function rules(): array
    {
        return [
            'student_alumni_id' => ['required', 'integer', 'exists:students_alumni,id'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'job_application_id' => ['nullable', 'integer', 'exists:job_applications,id'],
            'placement_status_id' => ['nullable', 'integer', 'exists:standard_types,id'],
            'accepted_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date', 'after_or_equal:accepted_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_alumni_id.required' => 'Data siswa atau alumni wajib dipilih.',
            'student_alumni_id.exists' => 'Data siswa atau alumni tidak ditemukan.',
            'company_id.required' => 'Akun HRD belum terhubung dengan data perusahaan.',
            'company_id.exists' => 'Perusahaan yang dipilih tidak valid.',
            'job_application_id.exists' => 'Lamaran pekerjaan yang dipilih tidak valid.',
            'placement_status_id.exists' => 'Status penempatan yang dipilih tidak valid.',
            'accepted_date.date' => 'Format tanggal diterima tidak valid.',
            'start_date.date' => 'Format tanggal mulai kerja tidak valid.',
            'start_date.after_or_equal' => 'Tanggal masuk kerja tidak boleh lebih awal dari tanggal diterima.',
        ];
    }
}
